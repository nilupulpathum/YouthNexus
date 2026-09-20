<?php

require_once __DIR__ . '/Audit.php';

/**
 * Annualaudit Controller Alias
 *
 * Provides backwards and semantic URL routing compatibility for `/annualaudit`
 * delegating directly to the primary `Audit` controller.
 */
class Annualaudit extends Audit {
    // Inherits all actions: index(), rerun(), clarify(), signoff(), export(), resolveflag()
}
