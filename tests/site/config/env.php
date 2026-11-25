<?php

// Accept both the browser test's loopback origin and this orb's HTTPS portal.
return ['url' => array_filter(['http://localhost:' . (getenv('PORT') ?: 8765), getenv('PUBLIC_URL')])];
