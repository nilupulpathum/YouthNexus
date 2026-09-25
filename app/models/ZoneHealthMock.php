<?php

/**
 * ZoneHealthMock — shared presentation-only mock for the zonal monitor
 * pages (coordinator `zonalcoordinator/clubs`, secretary
 * `zonalsecretary/clubs`).
 *
 * No database reads or writes. Score bands follow the divisional Figma
 * standard (owner direction 2026-09-12): Healthy 85-100, At Risk 50-84,
 * Dormant below 50 or inactivity. Components sum to the total per the
 * 40/30/30 health rule (Events 40, Finances 30, Attendance 30).
 * Backend contract lands in Z12.
 */
class ZoneHealthMock {

    /**
     * Zone mock: 5 clubs across 3 divisions of Gampaha Zone, matching the
     * divisional monitor counts (3 Healthy, 1 At Risk, 1 Dormant).
     */
    public function zoneClubs() {
        return [
            ['id' => 'GC-001', 'name' => 'Gampaha Chess Club', 'division' => 'Gampaha Division', 'score' => 94, 'band' => 'healthy', 'status' => 'Healthy', 'status_key' => 'approved', 'members' => 32, 'members_note' => '32 active members',
                'about' => 'Promoting strategic thinking among youth through inter-club chess leagues, school outreach and weekend coaching in the Gampaha region.',
                'category' => 'Sports & Strategy', 'location' => 'Western Province, Gampaha', 'established' => 'March 2021',
                'avg_attendance' => '92%', 'attendance_trend' => '+5%',
                'trigger' => 'No trigger — club is healthy',
                'recent_events' => [
                    ['name' => 'Inter-Club Chess League', 'venue' => 'Gampaha Town Hall', 'date' => 'Aug 24, 2026', 'status' => 'Completed', 'status_key' => 'completed'],
                    ['name' => 'Schools Coaching Camp', 'venue' => 'Bandaranayake College', 'date' => 'Jul 12, 2026', 'status' => 'Completed', 'status_key' => 'completed'],
                    ['name' => 'Zonal Rapid Championship', 'venue' => 'Gampaha Town Hall', 'date' => 'Oct 18, 2026', 'status' => 'Upcoming', 'status_key' => 'pending'],
                ],
                'execs' => [
                    ['name' => 'D. Rajapaksa', 'role' => 'President'],
                    ['name' => 'S. Fernando', 'role' => 'Secretary'],
                    ['name' => 'T. Wickramasinghe', 'role' => 'Treasurer'],
                ],
                'events' => ['points' => 38, 'max' => 40], 'finances' => ['points' => 28, 'max' => 30], 'attendance' => ['points' => 28, 'max' => 30]],
            ['id' => 'GC-002', 'name' => 'Minuwangoda Debate Society', 'division' => 'Gampaha Division', 'score' => 91, 'band' => 'healthy', 'status' => 'Healthy', 'status_key' => 'approved', 'members' => 28, 'members_note' => '28 active members',
                'about' => 'Building confident young speakers through weekly debates, public-speaking clinics and inter-division oratory contests.',
                'category' => 'Education & Media', 'location' => 'Western Province, Minuwangoda', 'established' => 'June 2022',
                'avg_attendance' => '89%', 'attendance_trend' => '+3%',
                'trigger' => 'No trigger — club is healthy',
                'recent_events' => [
                    ['name' => 'Oratory Contest Heats', 'venue' => 'Minuwangoda Hall', 'date' => 'Aug 09, 2026', 'status' => 'Completed', 'status_key' => 'completed'],
                    ['name' => 'Public-Speaking Clinic', 'venue' => 'Community Centre', 'date' => 'Jun 28, 2026', 'status' => 'Completed', 'status_key' => 'completed'],
                    ['name' => 'Zonal Debate Finals', 'venue' => 'Gampaha Town Hall', 'date' => 'Nov 02, 2026', 'status' => 'Upcoming', 'status_key' => 'pending'],
                ],
                'execs' => [
                    ['name' => 'N. De Silva', 'role' => 'President'],
                    ['name' => 'K. Perera', 'role' => 'Secretary'],
                    ['name' => 'R. Jayasuriya', 'role' => 'Treasurer'],
                ],
                'events' => ['points' => 37, 'max' => 40], 'finances' => ['points' => 27, 'max' => 30], 'attendance' => ['points' => 27, 'max' => 30]],
            ['id' => 'GC-003', 'name' => 'Ja-Ela Tech & Coding Guild', 'division' => 'Ja-Ela Division', 'score' => 88, 'band' => 'healthy', 'status' => 'Healthy', 'status_key' => 'approved', 'members' => 41, 'members_note' => '41 active members',
                'about' => 'Hands-on technology guild running coding bootcamps, robotics taster days and community repair clinics across Ja-Ela.',
                'category' => 'Technology & Innovation', 'location' => 'Western Province, Ja-Ela', 'established' => 'January 2023',
                'avg_attendance' => '85%', 'attendance_trend' => '+5%',
                'trigger' => 'No trigger — club is healthy',
                'recent_events' => [
                    ['name' => 'Coding Bootcamp Week', 'venue' => 'Ja-Ela Library', 'date' => 'Aug 17, 2026', 'status' => 'Completed', 'status_key' => 'completed'],
                    ['name' => 'Repair Clinic Saturday', 'venue' => 'Community Hall', 'date' => 'Jul 05, 2026', 'status' => 'Completed', 'status_key' => 'completed'],
                    ['name' => 'Robotics Taster Day', 'venue' => 'St. Mary College', 'date' => 'Oct 25, 2026', 'status' => 'Upcoming', 'status_key' => 'pending'],
                ],
                'execs' => [
                    ['name' => 'A. Ranasinghe', 'role' => 'President'],
                    ['name' => 'M. Perera', 'role' => 'Secretary'],
                    ['name' => 'S. Wickramasinghe', 'role' => 'Treasurer'],
                ],
                'events' => ['points' => 36, 'max' => 40], 'finances' => ['points' => 26, 'max' => 30], 'attendance' => ['points' => 26, 'max' => 30]],
            ['id' => 'GC-004', 'name' => 'Negombo Robotics Circle', 'division' => 'Negombo Division', 'score' => 61, 'band' => 'atrisk', 'status' => 'At Risk', 'status_key' => 'pending', 'members' => 17, 'members_note' => '17 active members',
                'about' => 'Coastal youth robotics circle — kit-based builds and lagoon-cleanup tech projects. Attendance slipped two quarters in a row.',
                'category' => 'Technology & Innovation', 'location' => 'Western Province, Negombo', 'established' => 'September 2023',
                'avg_attendance' => '58%', 'attendance_trend' => '-6%',
                'trigger' => 'Score below 70 for 2 consecutive quarters',
                'recent_events' => [
                    ['name' => 'Kit Build Night', 'venue' => 'Negombo Library', 'date' => 'Jul 30, 2026', 'status' => 'Completed', 'status_key' => 'completed'],
                    ['name' => 'Lagoon Sensor Trial', 'venue' => 'Negombo Lagoon', 'date' => 'Jun 14, 2026', 'status' => 'Completed', 'status_key' => 'completed'],
                    ['name' => 'Recruitment Drive', 'venue' => 'Community Hall', 'date' => 'Oct 11, 2026', 'status' => 'Upcoming', 'status_key' => 'pending'],
                ],
                'execs' => [
                    ['name' => 'L. Mendis', 'role' => 'President'],
                    ['name' => 'P. Cooray', 'role' => 'Secretary'],
                    ['name' => 'H. Fernando', 'role' => 'Treasurer'],
                ],
                'events' => ['points' => 24, 'max' => 40], 'finances' => ['points' => 19, 'max' => 30], 'attendance' => ['points' => 18, 'max' => 30]],
            ['id' => 'GC-005', 'name' => 'Mirigama Rural Youth', 'division' => 'Negombo Division', 'score' => 34, 'band' => 'dormant', 'status' => 'Dormant', 'status_key' => 'dormant', 'members' => 0, 'members_note' => 'No recent activity',
                'about' => 'Rural youth circle focused on home-garden and literacy drives. No verified activity this quarter — executive seats are vacant.',
                'category' => 'Community & Environment', 'location' => 'Western Province, Mirigama', 'established' => 'May 2020',
                'avg_attendance' => '21%', 'attendance_trend' => '-12%',
                'trigger' => 'Score below 50 — dormant, no verified activity',
                'recent_events' => [
                    ['name' => 'Home-Garden Drive', 'venue' => 'Mirigama Grounds', 'date' => 'Mar 08, 2026', 'status' => 'Completed', 'status_key' => 'completed'],
                    ['name' => 'Literacy Camp', 'venue' => 'Village Hall', 'date' => 'Jan 19, 2026', 'status' => 'Completed', 'status_key' => 'completed'],
                    ['name' => 'Revival Meeting', 'venue' => 'Village Hall', 'date' => 'Oct 30, 2026', 'status' => 'Upcoming', 'status_key' => 'pending'],
                ],
                'execs' => [
                    ['name' => 'Vacant', 'role' => 'President'],
                    ['name' => 'Vacant', 'role' => 'Secretary'],
                    ['name' => 'Vacant', 'role' => 'Treasurer'],
                ],
                'events' => ['points' => 12, 'max' => 40], 'finances' => ['points' => 11, 'max' => 30], 'attendance' => ['points' => 11, 'max' => 30]],
        ];
    }

    /**
     * Per-division average club health, computed from the club mock so the
     * strip can never drift from the cards.
     */
    public function divisionAverages($clubs) {
        $groups = [];
        foreach ($clubs as $c) {
            $groups[$c['division']][] = $c['score'];
        }
        $out = [];
        foreach ($groups as $division => $scores) {
            $avg = (int) round(array_sum($scores) / count($scores));
            $band = $avg >= 85 ? 'healthy' : ($avg >= 50 ? 'atrisk' : 'dormant');
            $out[] = [
                'division'   => $division,
                'average'    => $avg,
                'clubs'      => count($scores),
                'band'       => $band,
                'status'     => $band === 'healthy' ? 'Healthy' : ($band === 'atrisk' ? 'At Risk' : 'Dormant'),
                'status_key' => $band === 'healthy' ? 'approved' : ($band === 'atrisk' ? 'pending' : 'dormant'),
            ];
        }
        usort($out, static function ($a, $b) {
            return $b['average'] <=> $a['average'];
        });
        return $out;
    }

    /**
     * Zone aggregate tiles, computed from the club mock.
     */
    public function zoneHealth($clubs) {
        $n = max(1, count($clubs));
        $sum = static function ($key) use ($clubs) {
            $t = 0;
            foreach ($clubs as $c) {
                $t += $c[$key]['points'];
            }
            return $t;
        };
        $events = (int) round($sum('events') / $n);
        $finances = (int) round($sum('finances') / $n);
        $attendance = (int) round($sum('attendance') / $n);
        $score = $events + $finances + $attendance;
        return [
            'score'  => $score,
            'label'  => $score >= 85 ? 'Green' : ($score >= 50 ? 'Yellow' : 'Red'),
            'state'  => $score >= 85 ? 'Active' : ($score >= 50 ? 'Watch' : 'Intervene'),
            'events' => ['points' => $events, 'max' => 40],
            'finances' => ['points' => $finances, 'max' => 30],
            'attendance' => ['points' => $attendance, 'max' => 30],
            'healthy' => count(array_filter($clubs, static function ($c) { return $c['band'] === 'healthy'; })),
            'atrisk'  => count(array_filter($clubs, static function ($c) { return $c['band'] === 'atrisk'; })),
            'dormant' => count(array_filter($clubs, static function ($c) { return $c['band'] === 'dormant'; })),
        ];
    }
}
