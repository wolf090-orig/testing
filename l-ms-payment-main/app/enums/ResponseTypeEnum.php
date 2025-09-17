<?php

namespace app\enums;

enum ResponseTypeEnum: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
    case WARNING = 'warning';
}
