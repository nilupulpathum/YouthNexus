<?php

/**
 * Local regression test for the rejected club registration lifecycle.
 *
 * Covers: inspect rejected registrations (with the president's rejection
 * reason), edit the details and reapply for approval, and delete the
 * registration request.
 *
 * Usage: c:\xampp\php\php.exe database\test_rejected_registration_lifecycle.php
 *
 * Synthetic data only. Every row created here is removed before exit.
 */

require_once __DIR__ . '/../app/core/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/ClubMemberRegistrationValidator.php';
require_once __DIR__ . '/../app/models/UserModel.php';

function expectRejectedLifecycle($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function findRejectedRow(array $rows, $userId) {
    foreach ($rows as $row) {
        if ((int) $row->user_id === (int) $userId) {
            return $row;
        }
    }
    return null;
}

function hasUser(array $rows, $userId) {
    foreach ($rows as $row) {
        if ((int) $row->user_id === (int) $userId) {
            return true;
        }
    }
    return false;
}

$pdo = Database::getInstance()->getConnection();
$userModel = new UserModel();
$createdUserIds = [];

try {
    echo "=== Rejected Registration Lifecycle Regression Test ===\n";

    $club = $pdo->query('SELECT club_id, division_id FROM Club WHERE division_id IS NOT NULL ORDER BY club_id LIMIT 1')->fetch();
    expectRejectedLifecycle((bool) $club, 'The local database needs one club with a division assignment.');
    $clubId = (int) $club->club_id;
    $divisionId = (int) $club->division_id;
    $otherClubId = (int) $pdo->query('SELECT COALESCE(MAX(club_id), 0) + 1 FROM Club')->fetchColumn();

    $suffix = strtoupper(bin2hex(random_bytes(4)));

    // --- inspect -------------------------------------------------------
    $candidateId = $userModel->registerClubMember(
        $clubId,
        $divisionId,
        'Rejected Lifecycle',
        "lifecycle-{$suffix}@example.test",
        '+94 77 555 0001',
        '1 Lifecycle Lane',
        'LC' . $suffix
    );
    $createdUserIds[] = $candidateId;
    expectRejectedLifecycle($candidateId > 0, 'Could not create the synthetic candidate.');

    expectRejectedLifecycle(
        $userModel->rejectClubMember($clubId, $candidateId) === 1,
        'The synthetic candidate must be rejectable.'
    );
    $pdo->prepare(
        "INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
         VALUES ((SELECT user_id FROM User WHERE role = 'ClubPresident' AND club_id = ? LIMIT 1), 'REJECT_MEMBER', 'User', ?, ?)"
    )->execute([$clubId, $candidateId, 'Documents were incomplete.']);

    $rejected = $userModel->getClubRejected($clubId);
    $row = findRejectedRow($rejected, $candidateId);
    expectRejectedLifecycle($row !== null, 'A rejected registration must be listed for the secretary.');
    expectRejectedLifecycle(
        trim((string) $row->rejection_reason) === 'Documents were incomplete.',
        'The rejection reason must be shown to the secretary.'
    );
    expectRejectedLifecycle(!empty($row->rejected_at), 'The rejection date must be shown to the secretary.');
    expectRejectedLifecycle(
        !hasUser($userModel->getClubRoster($clubId), $candidateId) && !hasUser($userModel->getClubPending($clubId), $candidateId),
        'A rejected registration must stay out of the active roster and the pending queue.'
    );
    echo "1. Inspect rejected registrations with reason: PASS\n";

    // --- club scope ----------------------------------------------------
    expectRejectedLifecycle(
        $userModel->updateRejectedRegistration($otherClubId, $candidateId, 'Scope Probe', "scope-{$suffix}@example.test", '0775550009', '9 Scope Road', 'SC' . $suffix) === 0,
        'A secretary from another club must not edit this registration.'
    );
    expectRejectedLifecycle(
        $userModel->deleteRejectedRegistration($otherClubId, $candidateId)['deleted'] === false,
        'A secretary from another club must not delete this registration.'
    );
    echo "2. Club scope on edit and delete: PASS\n";

    // --- edit + reapply ------------------------------------------------
    expectRejectedLifecycle(
        $userModel->emailOrNicTaken("lifecycle-{$suffix}@example.test", 'LC' . $suffix, $candidateId) === false,
        'A registration must not collide with its own email and NIC when edited.'
    );
    expectRejectedLifecycle(
        $userModel->updateRejectedRegistration($clubId, $candidateId, 'Corrected Name', "lifecycle-fixed-{$suffix}@example.test", '0775550002', '2 Corrected Lane', 'FX' . $suffix) === 1,
        'The secretary must be able to edit and resubmit a rejected registration.'
    );
    $updated = $userModel->findByUserId($candidateId);
    expectRejectedLifecycle($updated && $updated->first_name === 'Corrected' && $updated->last_name === 'Name', 'The edited name must be stored.');
    expectRejectedLifecycle($updated && $updated->email === "lifecycle-fixed-{$suffix}@example.test", 'The edited email must be stored.');
    expectRejectedLifecycle($updated && $updated->status === 'Active' && $updated->membership_status === 'Inactive', 'A resubmitted registration must return to the approval queue.');
    expectRejectedLifecycle(
        hasUser($userModel->getClubPending($clubId), $candidateId),
        'A resubmitted registration must appear in the president pending queue.'
    );
    expectRejectedLifecycle(
        findRejectedRow($userModel->getClubRejected($clubId), $candidateId) === null,
        'A resubmitted registration must leave the rejected list.'
    );
    expectRejectedLifecycle(
        $userModel->approveClubMember($clubId, $candidateId) === 1,
        'The president must be able to approve the resubmitted registration.'
    );
    echo "3. Edit and reapply for approval: PASS\n";

    // --- delete --------------------------------------------------------
    $rejectAgain = $userModel->registerClubMember(
        $clubId,
        $divisionId,
        'Delete Me',
        "delete-{$suffix}@example.test",
        '0775550003',
        '3 Delete Lane',
        'DL' . $suffix
    );
    $createdUserIds[] = $rejectAgain;
    $userModel->rejectClubMember($clubId, $rejectAgain);
    $pdo->prepare(
        "INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
         VALUES ((SELECT user_id FROM User WHERE role = 'ClubPresident' AND club_id = ? LIMIT 1), 'REJECT_MEMBER', 'User', ?, ?)"
    )->execute([$clubId, $rejectAgain, 'Withdrawn by the candidate.']);

    $blockerId = $pdo->prepare('INSERT INTO PasswordReset (user_id, otp_code, expires_at, is_used, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), FALSE, NOW())');
    $blockerId->execute([$rejectAgain, 'LIFECYCLE' . $suffix]);

    $blocked = $userModel->deleteRejectedRegistration($clubId, $rejectAgain);
    expectRejectedLifecycle($blocked['deleted'] === false, 'Delete must be refused while a dependent record exists.');
    expectRejectedLifecycle(!empty($blocked['blockers']), 'A refused delete must name the blocking records.');
    expectRejectedLifecycle($userModel->findByUserId($rejectAgain) !== false, 'A refused delete must keep the registration.');

    $pdo->prepare('DELETE FROM PasswordReset WHERE user_id = ?')->execute([$rejectAgain]);
    $deleted = $userModel->deleteRejectedRegistration($clubId, $rejectAgain);
    expectRejectedLifecycle($deleted['deleted'] === true, 'The secretary must be able to delete a rejected registration.');
    expectRejectedLifecycle($userModel->findByUserId($rejectAgain) === false, 'A deleted registration must be gone.');

    $rejectedRows = $pdo->prepare("SELECT COUNT(*) c FROM AuditLog WHERE target_entity = 'User' AND target_id = ?");
    $rejectedRows->execute([$rejectAgain]);
    expectRejectedLifecycle((int) $rejectedRows->fetch()->c > 0, 'Deleting a registration must not erase its audit trail.');
    echo "4. Delete with dependency guard: PASS\n";

    echo "=== ALL REJECTED REGISTRATION LIFECYCLE TESTS PASSED ===\n";
} finally {
    foreach ($createdUserIds as $userId) {
        $pdo->prepare('DELETE FROM PasswordReset WHERE user_id = ?')->execute([(int) $userId]);
        $pdo->prepare('DELETE FROM User WHERE user_id = ?')->execute([(int) $userId]);
    }
}
