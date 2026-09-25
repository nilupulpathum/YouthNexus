-- Optional local visual-test data for Monitor Club Health.
-- It reuses images already tracked under public/assets/images.
-- Run only in a local development database.

UPDATE ClubApplication ca
INNER JOIN Club c ON c.source_application_id = ca.application_id
SET ca.club_logo_path = CASE MOD(c.club_id, 4)
    WHEN 0 THEN '/assets/images/v381_335.png'
    WHEN 1 THEN '/assets/images/v271_471.png'
    WHEN 2 THEN '/assets/images/v271_513.png'
    ELSE '/assets/images/v271_575.png'
END
WHERE c.status IN ('Active', 'Flagged');

UPDATE User u
INNER JOIN Club c ON c.club_id = u.club_id
SET u.profile_picture_url = CASE u.role
    WHEN 'ClubPresident' THEN '/assets/images/v271_591.png'
    WHEN 'ClubSecretary' THEN '/assets/images/v271_583.png'
    WHEN 'ClubTreasurer' THEN '/assets/images/v271_523.png'
    ELSE u.profile_picture_url
END
WHERE u.role IN ('ClubPresident', 'ClubSecretary', 'ClubTreasurer')
  AND u.status = 'Active'
  AND c.status IN ('Active', 'Flagged');

SELECT c.club_id, c.club_name, ca.club_logo_path
FROM Club c
LEFT JOIN ClubApplication ca ON ca.application_id = c.source_application_id
WHERE c.status IN ('Active', 'Flagged')
ORDER BY c.club_id;

SELECT u.user_id, u.first_name, u.last_name, u.role, u.club_id,
       u.profile_picture_url
FROM User u
WHERE u.role IN ('ClubPresident', 'ClubSecretary', 'ClubTreasurer')
  AND u.status = 'Active'
ORDER BY u.club_id, FIELD(u.role, 'ClubPresident', 'ClubSecretary', 'ClubTreasurer');
