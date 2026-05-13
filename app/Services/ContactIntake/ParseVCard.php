<?php

namespace App\Services\ContactIntake;

use Sabre\VObject\Reader;

class ParseVCard
{
    public function parse(string $vcf): array
    {
        $vObject = Reader::read($vcf, Reader::OPTION_FORGIVING);

        $cards = collect($vObject->getComponents())
            ->filter(fn ($c) => $c->name === 'VCARD');

        if ($cards->count() !== 1) {
            throw new \InvalidArgumentException('Please upload a single contact card.');
        }

        $card = $cards->first();

        return [
            'name'    => $this->get($card, 'FN'),
            'phone'   => $this->firstTel($card),
            'email'   => $this->get($card, 'EMAIL'),
            'company' => $this->get($card, 'ORG'),
            'raw_vcf' => $vcf,
        ];
    }

    private function get($card, string $prop): string
    {
        return $card->$prop ? (string) $card->$prop : '';
    }

    private function firstTel($card): string
    {
        if (! $card->TEL) {
            return '';
        }

        foreach ($card->TEL as $tel) {
            $v = (string) $tel;
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    }
}
