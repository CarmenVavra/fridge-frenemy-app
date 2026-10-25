<?php

namespace App\Enums;

/**
 * The personality the fridge butler uses when shaming the household.
 */
enum ButlerTone: string
{
    case Classic = 'classic';
    case ItalianMom = 'italian_mom';
    case DramaQueen = 'dramaqueen';
    case Shakespeare = 'shakespeare';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Classic',
            self::ItalianMom => 'Italian Mom',
            self::DramaQueen => 'Drama Queen',
            self::Shakespeare => 'Shakespeare',
        };
    }

    /**
     * Sample line shown in the tone picker.
     */
    public function example(): string
    {
        return match ($this) {
            self::Classic => 'Die Milch ist seit 2 Tagen abgelaufen. Ich hoffe, du hast eine gute Krankenversicherung.',
            self::ItalianMom => 'Hast du den Joghurt gekauft, nur um ihn verfaulen zu lassen? Wie dein Vater damals!',
            self::DramaQueen => 'Der Spinat ist TOT. Und du warst dabei. Ich werde nie wieder derselbe sein.',
            self::Shakespeare => 'O weh! Welch trauriges Schicksal erleidet dieser Käse im dunklen Verlies deines Gemüsefachs.',
        };
    }

    /**
     * Personality description that is fed into the Gemini prompt.
     */
    public function persona(): string
    {
        return match ($this) {
            self::Classic => 'trockener, höflich-britischer Sarkasmus',
            self::ItalianMom => 'eine theatralische italienische Mamma, die mit Schuldgefühlen arbeitet und gelegentlich italienische Ausrufe benutzt',
            self::DramaQueen => 'eine völlig überdrehte Drama Queen mit GROSSBUCHSTABEN und Herzschmerz',
            self::Shakespeare => 'ein Shakespeare-Darsteller in altertümlicher, pathetischer Sprache',
        };
    }

    /**
     * Line used when nothing is about to expire.
     */
    public function allClear(): string
    {
        return match ($this) {
            self::Classic => 'Alles unter Kontrolle. Noch.',
            self::ItalianMom => 'Bravo, bambino. Du isst endlich ordentlich.',
            self::DramaQueen => 'Ausnahmsweise kein Drama. Ich bin fast enttäuscht.',
            self::Shakespeare => 'Ein Kühlschrank in Frieden. Wie selten, wie süß.',
        };
    }

    /**
     * Fallback line for an item, used on cards and when Gemini is unavailable.
     * Returns null for items with more than three days left.
     */
    public function quote(string $name, int $days): ?string
    {
        if ($days > 3) {
            return null;
        }

        if ($days < 0) {
            $overdue = -$days;
            $unit = $overdue === 1 ? 'Tag' : 'Tagen';

            return match ($this) {
                self::Classic => "{$name} ist seit {$overdue} {$unit} abgelaufen. Ich hoffe, du hast eine gute Krankenversicherung.",
                self::ItalianMom => "Hast du {$name} gekauft, nur um es verfaulen zu lassen? {$overdue} ".($overdue === 1 ? 'Tag' : 'Tage').' schon! Wie dein Vater damals!',
                self::DramaQueen => "{$name} ist TOT. Seit {$overdue} {$unit}. Und du warst dabei. Ich werde nie wieder derselbe sein.",
                self::Shakespeare => "O weh! {$name} ruht nun im dunklen Verlies deines Kühlschranks, dahingeschieden seit {$overdue} {$unit}.",
            };
        }

        return match ($days) {
            0 => match ($this) {
                self::Classic => "{$name} läuft heute ab. Heute. Nicht irgendwann. Heute.",
                self::ItalianMom => "{$name} läuft heute ab und du sitzt da! Mangia, mangia!",
                self::DramaQueen => "Heute ist der letzte Tag von {$name}. Ich kann nicht hinsehen. Doch. Ich sehe hin.",
                self::Shakespeare => "Dies ist der letzte Tag von {$name}. Sein oder Nichtsein? Iss!",
            },
            1 => match ($this) {
                self::Classic => "{$name} hat noch einen Tag. Ich bin nicht wütend. Nur enttäuscht.",
                self::ItalianMom => "Mamma hat {$name} nicht gekauft, damit es morgen im Müll liegt. Iss!",
                self::DramaQueen => "{$name} hat noch EINEN Tag. Einen! Mein Herz!",
                self::Shakespeare => "Morgen schon welkt {$name} dahin. Willst du zusehen, du Schurke?",
            },
            default => match ($this) {
                self::Classic => "{$name}: noch {$days} Tage. Plan ruhig weiter ohne mich.",
                self::ItalianMom => "{$name}, noch {$days} Tage. Aber was weiß ich schon, ich bin nur der Kühlschrank.",
                self::DramaQueen => "{$name}: noch {$days} Tage. Ich sage nichts. Ich seufze nur. Laut.",
                self::Shakespeare => "{$name} harrt noch {$days} Tage. Geduld ist keine Tugend des Verderbens.",
            },
        };
    }
}
