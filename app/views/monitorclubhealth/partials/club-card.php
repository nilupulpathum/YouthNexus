                        <?php
                            $healthStatus = $club->health_status ?? 'Green';
                            $statusKey = strtolower($healthStatus); // 'green', 'yellow', 'red'
                            $score = (float)($club->overall_health_score ?? 0);
                            $formattedScore = number_format($score, 0);
                            $statusLabel = 'HEALTHY';
                            if ($healthStatus === 'Yellow') {
                                $statusLabel = 'AT RISK';
                            } elseif ($healthStatus === 'Red') {
                                $statusLabel = 'DORMANT';
                            }
                            $isFlagged = !empty($club->flagged) ? '1' : '0';
                            $membersCount = (int)($club->live_members ?? 0);
                            $membersText = $membersCount > 0 ? ($membersCount . ' active members') : 'No activity recorded';
                            $locationText = htmlspecialchars($club->division_name ?? $divisionName);

                            // Sourced data with honest fallbacks
                            $descText = !empty(trim($club->description ?? '')) ? trim($club->description) : 'No description provided.';
                            $estDateText = !empty($club->registration_date) ? date('F Y', strtotime($club->registration_date)) : 'Not available';
                            $clubCommittee = $committees[$club->club_id] ?? [];

                            // Circular initials avatar calculation
                            $words = explode(' ', trim($club->club_name));
                            $initials = '';
                            foreach ($words as $w) {
                                if (!empty($w) && ctype_alnum($w[0])) {
                                    $initials .= strtoupper($w[0]);
                                }
                            }
                            $clubInitials = substr($initials, 0, 2) ?: 'CL';
                        ?>
                        <div class="mch-card <?= $statusKey ?>"
                             data-id="<?= (int)$club->club_id ?>"
                             data-name="<?= htmlspecialchars($club->club_name, ENT_QUOTES) ?>"
                             data-code="<?= htmlspecialchars($club->club_code ?? '', ENT_QUOTES) ?>"
                             data-score="<?= $score ?>"
                             data-status="<?= htmlspecialchars($healthStatus, ENT_QUOTES) ?>"
                             data-flagged="<?= $isFlagged ?>"
                             data-members="<?= $membersCount ?>"
                             data-desc="<?= htmlspecialchars($descText, ENT_QUOTES) ?>"
                             data-division="<?= htmlspecialchars($club->division_name ?? $divisionName, ENT_QUOTES) ?>"
                             data-date="<?= htmlspecialchars($estDateText, ENT_QUOTES) ?>"
                             data-committee='<?= htmlspecialchars(json_encode($clubCommittee), ENT_QUOTES) ?>'>

                            <!-- 1. Circular Avatar & 2. Flagged Badge -->
                            <div class="mch-card-avatar-wrap">
                                <div class="mch-card-avatar <?= $statusKey ?>">
                                    <?php if (!empty($club->club_photo)): ?>
                                        <img src="<?= ROOT . htmlspecialchars($club->club_photo) ?>" alt="<?= htmlspecialchars($club->club_name) ?> profile image" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        <?= htmlspecialchars($clubInitials) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($isFlagged === '1' || $healthStatus === 'Red'): ?>
                                    <div class="mch-card-flag-badge" title="Flagged / Low Health Club requiring intervention">
                                        <svg viewBox="0 0 24 24"><path fill="#fff" d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/></svg>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Intervention Wrapper (Ensures consistent height across all cards) -->
                            <div class="mch-card-intervention-wrap">
                                <?php if ($isFlagged === '1' || $healthStatus === 'Red'): ?>
                                    <span class="mch-intervention-badge">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="margin-right: 4px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        INTERVENTION REQUIRED
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- 3. Score Row -->
                            <div class="mch-card-score-row">
                                <span class="mch-card-score-num <?= $statusKey ?>"><?= $formattedScore ?></span>
                                <span class="mch-card-score-denom">/100</span>
                            </div>

                            <!-- 4. Status Badge -->
                            <div class="mch-card-badge-wrap">
                                <span class="mch-card-badge <?= $statusKey ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </div>

                            <!-- 5. Club Name -->
                            <h3 class="mch-card-club-name"><?= htmlspecialchars($club->club_name) ?></h3>

                            <!-- 6. Location & Member Count -->
                            <p class="mch-card-subline"><?= $locationText ?> • <?= $membersText ?></p>

                            <!-- 7. Bottom View Details Button -->
                            <div class="mch-card-bottom">
                                <button type="button" class="mch-card-arrow-btn" title="View details for <?= htmlspecialchars($club->club_name) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                                </button>
                            </div>
                        </div>
