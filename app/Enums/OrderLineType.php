<?php

namespace App\Enums;

enum OrderLineType: string
{
    case Box = 'box';
    case AddOn = 'add_on';
}
