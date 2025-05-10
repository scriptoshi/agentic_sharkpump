<?php

namespace App\Enums;
enum LaunchpadStatus: string
{
	case PENDING = 'pending';
	case PREBOND = 'prebond';
	case BONDING = 'bonding';
	case FINALIZED = 'finalized';

}
