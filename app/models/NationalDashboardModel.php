<?php

class NationalDashboardModel extends Model {

    public function getTotalYouth() {
        return (int)$this->single(
            "SELECT COUNT(*) as total FROM User WHERE club_id IS NOT NULL AND status = 'Active'"
        )->total;
    }

    public function getTotalClubs() {
        return (int)$this->single(
            "SELECT COUNT(*) as total FROM Club WHERE status = 'Active'"
        )->total;
    }

    public function getTotalVolunteerHours() {
        return (int)$this->single(
            "SELECT COALESCE(SUM(hours),0) as total FROM VolunteerHistory WHERE status = 'Verified' AND YEAR(date) = YEAR(CURDATE())"
        )->total;
    }

    public function getTotalFunds() {
        return (float)$this->single(
            "SELECT COALESCE(SUM(current_balance),0) as total FROM Ledger WHERE status = 'Active'"
        )->total;
    }

    public function getHealthDistribution() {
        $rows = $this->resultSet(
            "SELECT health_status, COUNT(*) as c FROM Club WHERE status = 'Active' GROUP BY health_status"
        );
        $health = ['Green' => 0, 'Yellow' => 0, 'Red' => 0];
        foreach ($rows as $r) {
            $health[$r->health_status] = (int)$r->c;
        }
        return $health;
    }

    public function getPendingApplications() {
        return (int)$this->single(
            "SELECT COUNT(*) as total FROM ClubApplication WHERE status = 'Pending'"
        )->total;
    }

    public function getOverdueAudits() {
        return (int)$this->single(
            "SELECT COUNT(*) as total FROM Audit WHERE audit_status = 'Overdue'"
        )->total;
    }

    public function getUnresolvedFlags() {
        return (int)$this->single(
            "SELECT COUNT(*) as total FROM RedFlag WHERE status = 'Unresolved'"
        )->total;
    }

    public function getMissingReports() {
        return (int)$this->single(
            "SELECT COUNT(*) as total FROM Report WHERE status = 'Missing'"
        )->total;
    }

    public function getRecentActivity() {
        $sql = "SELECT al.*, u.first_name, u.last_name, u.role
                FROM AuditLog al
                JOIN User u ON al.actor_user_id = u.user_id
                ORDER BY al.timestamp DESC
                LIMIT 5";
        return $this->resultSet($sql);
    }
}
