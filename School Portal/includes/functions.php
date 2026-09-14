<?php
/** Small shared view helpers. */

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function grade_color(string $letter): string {
    if (str_starts_with($letter, 'A')) return 'grade-a';
    if (str_starts_with($letter, 'B')) return 'grade-b';
    if (str_starts_with($letter, 'C')) return 'grade-c';
    return 'grade-d';
}

function day_name(int $dow): string {
    $days = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
    return $days[$dow] ?? '';
}
