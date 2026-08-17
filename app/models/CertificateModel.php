<?php

class CertificateModel extends Model {

    /**
     * Create a certificate record.
     * @param array $data  Contains owner_type, owner_id, certificate_type, qr_code, pdf_url
     * @return int|false  The inserted certificate_id
     */
    public function createCertificate($data) {
        $this->query(
            "INSERT INTO Certificate (owner_type, owner_id, certificate_type, qr_code, pdf_url)
             VALUES (?, ?, ?, ?, ?)",
            [
                $data['owner_type'],
                $data['owner_id'],
                $data['certificate_type'] ?? 'ClubRegistration',
                $data['qr_code'] ?? null,
                $data['pdf_url'] ?? null,
            ]
        );
        $row = $this->single("SELECT LAST_INSERT_ID() AS id");
        return $row ? (int)$row->id : false;
    }

    /**
     * Find a certificate by owner.
     * @param string $ownerType  'Club' or 'Member'
     * @param int    $ownerId
     * @return object|false
     */
    public function findByOwner($ownerType, $ownerId) {
        return $this->single(
            "SELECT * FROM Certificate
             WHERE owner_type = ? AND owner_id = ?
             ORDER BY issued_at DESC LIMIT 1",
            [$ownerType, $ownerId]
        );
    }
}
