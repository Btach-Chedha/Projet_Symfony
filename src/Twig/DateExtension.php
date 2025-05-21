<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ago', [$this, 'formatAgo']),
        ];
    }

    /**
     * Formatte une date en "il y a X temps"
     */
    public function formatAgo(\DateTimeInterface $date): string
    {
        $now = new \DateTime();
        $interval = $now->diff($date);
        
        if ($interval->y > 0) {
            return $interval->y > 1 ? "il y a {$interval->y} ans" : "il y a un an";
        }
        
        if ($interval->m > 0) {
            return $interval->m > 1 ? "il y a {$interval->m} mois" : "il y a un mois";
        }
        
        if ($interval->d > 0) {
            return $interval->d > 1 ? "il y a {$interval->d} jours" : "hier";
        }
        
        if ($interval->h > 0) {
            return $interval->h > 1 ? "il y a {$interval->h} heures" : "il y a une heure";
        }
        
        if ($interval->i > 0) {
            return $interval->i > 1 ? "il y a {$interval->i} minutes" : "il y a une minute";
        }
        
        return "à l'instant";
    }
}