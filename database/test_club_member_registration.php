<?php

/**
 * Local regression test for the club member registration lifecycle.
 *
 * Usage: c:\xampp\php\php.exe database\test_club_member_registration.php
 *
 * It uses synthetic data and removes every created row before exit.
 */

require_once __DIR__ . '/../app/core/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/ClubMemberRegistrationValidator.php';
require_once __DIR__ . '/../app/models/UserModel.php';

function expectClubMemberRegistration($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function rosterContainsUser(array $roster, $userId) {
    foreach ($roster as $member) {
        if ((int) $member->user_id === (int) $userId) {
            return true;
        }
    }
    return false;
}

$pdo = Database::getInstance()->getConnection();
$userModel = new UserModel();
$createdUserIds = [];

try {
    echo "=== Club Member Registration Regression Test ===\n";

    $valid = ClubMemberRegistrationValidator::validate([
        'name' => "Demo\tCandidate",
        'email' => 'demo.candidate@example.test',
        'phone' => '+94 77 123 4567',
        'address' => '1 Test Lane, Colombo',
        'nic' => '200012345678',
    ]);
    expectClubMemberRegistration(empty($valid['errors']), 'A valid candidate should pass validation.');

    $invalid = ClubMemberRegistrationValidator::validate([
        'name' => 'Onlyname',
        'email' => str_repeat('a', 95) . '@test.com',
        'phone' => 'not-a-phone',
        'address' => str_repeat('x', 256),
        'nic' => 'bad nic',
    ]);
    expectClubMemberRegistration(isset($invalid['errors']['name']), 'A single-name candidate must be rejected.');
    expectClubMemberRegistration(isset($invalid['errors']['email']), 'An overlong email must be rejected.');
    expectClubMemberRegistration(isset($invalid['errors']['phone']), 'An invalid phone must be rejected.');
    expectClubMemberRegistration(isset($invalid['errors']['address']), 'An overlong address must be rejected.');
    expectClubMemberRegistration(isset($invalid['errors']['nic']), 'An invalid NIC must be rejected.');
    echo "1. Server input boundaries: PASS\n";

    $clubs = $pdo->query(
        "SELECT club_id, division_id FROM Club WHERE division_id IS NOT NULL ORDER BY club_id LIMIT 2"
    )->fetchAll();
    expectClubMemberRegistration(count($clubs) >= 1, 'The local database needs one club with a division assignment.');

    $suffix = bin2hex(random_bytes(4));
    $firstClub = $clubs[0];
    $otherClubId = isset($clubs[1])
        ? (int) $clubs[1]->club_id
        : (int) $pdo->query('SELECT COALESCE(MAX(club_id), 0) + 1 FROM Club')->fetchColumn();
    $email = "member-regression-{$suffix}@example.test";
    $nic = 'TEST' . strtoupper($suffix);

    $candidateId = $userModel->registerClubMember(
        (int) $firstClub->club_id,
        (int) $firstClub->division_id,
        "Regression\tCandidate",
        $email,
        '+94 77 123 4567',
        '1 Test Lane, Colombo',
        $nic
    );
    $createdUserIds[] = $candidateId;
    expectClubMemberRegistration($candidateId > 0, 'Registration must create a candidate.');
    expectClubMemberRegistration(
        rosterContainsUser($userModel->getClubPending((int) $firstClub->club_id), $candidateId),
        'A new candidate must appear in its club pending queue.'
    );
    $candidate = $userModel->findByUserId($candidateId);
    expectClubMemberRegistration(
        $candidate && $candidate->first_name === 'Regression' && $candidate->last_name === 'Candidate',
        'Whitespace-separated names must be stored as distinct first and last names.'
    );
    expectClubMemberRegistration(
        $userModel->emailOrNicTaken("unused-{$suffix}@example.test", $nic),
        'The candidate NIC must be reserved globally.'
    );
    expectClubMemberRegistration(
        $userModel->registerClubMember(
            (int) $firstClub->club_id,
            (int) $firstClub->division_id,
            'Duplicate Candidate',
            $email,
            '+94 77 123 4569',
            '3 Test Lane, Colombo',
            'DUPLICATE' . strtoupper($suffix)
        ) === 0,
        'A duplicate email must not create a second candidate.'
    );
    $userModel->updatePassword($email, password_hash('local-test-password', PASSWORD_DEFAULT));
    expectClubMemberRegistration(
        !$userModel->verifyLogin($email, 'local-test-password'),
        'A pending club member must not authenticate before president approval.'
    );
    echo "2. Create and pending queue: PASS\n";

    expectClubMemberRegistration(
        $userModel->approveClubMember($otherClubId, $candidateId) === 0,
        'A president from another club must not approve this candidate.'
    );
    expectClubMemberRegistration(
        $userModel->approveClubMember((int) $firstClub->club_id, $candidateId) === 1,
        'The candidate must be approvable by their own club president.'
    );
    expectClubMemberRegistration(
        rosterContainsUser($userModel->getClubRoster((int) $firstClub->club_id), $candidateId),
        'An approved candidate must appear in the active roster.'
    );
    expectClubMemberRegistration(
        (bool) $userModel->verifyLogin($email, 'local-test-password'),
        'An approved member with an activated password must authenticate.'
    );
    echo "3. Club-scoped approval: PASS\n";

    $rejectionId = $userModel->registerClubMember(
        (int) $firstClub->club_id,
        (int) $firstClub->division_id,
        'Rejected Candidate',
        "member-rejection-{$suffix}@example.test",
        '+94 77 123 4568',
        '2 Test Lane, Colombo',
        'REJECT' . strtoupper($suffix)
    );
    $createdUserIds[] = $rejectionId;
    expectClubMemberRegistration(
        $userModel->rejectClubMember($otherClubId, $rejectionId) === 0,
        'A president from another club must not reject this candidate.'
    );
    expectClubMemberRegistration(
        $userModel->rejectClubMember((int) $firstClub->club_id, $rejectionId) === 1,
        'The candidate must be rejectable by their own club president.'
    );
    $rejected = $userModel->findByUserId($rejectionId);
    expectClubMemberRegistration($rejected && $rejected->status === 'Disabled', 'Rejection must preserve a disabled audit-trail record.');
    echo "4. Club-scoped rejection: PASS\n";

    echo "=== ALL CLUB MEMBER REGISTRATION TESTS PASSED ===\n";
} finally {
    foreach ($createdUserIds as $userId) {
        $pdo->prepare('DELETE FROM User WHERE user_id = ?')->execute([(int) $userId]);
    }
}
