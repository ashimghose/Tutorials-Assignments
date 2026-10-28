<?php

namespace App\Policies;

/**
 * ACA-2: admins manage add-ons; orders keep a copy of the name and price.
 */
class ServiceAddonPolicy extends AdminPolicy {}
