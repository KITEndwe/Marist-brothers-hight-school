<?php
/** Small shared view helpers. */

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function grade_color(string $letter): string {
    if (substr($letter, 0, 1) === 'A') return 'grade-a';
    if (substr($letter, 0, 1) === 'B') return 'grade-b';
    if (substr($letter, 0, 1) === 'C') return 'grade-c';
    return 'grade-d';
}

function score_to_letter(float $score): string {
    if ($score >= 90) return 'A';
    if ($score >= 80) return 'B+';
    if ($score >= 70) return 'B';
    if ($score >= 60) return 'C';
    if ($score >= 50) return 'D';
    return 'F';
}

function day_name(int $dow): string {
    $days = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
    return $days[$dow] ?? '';
}