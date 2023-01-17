<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use PhpUnitConversion\Exception\UnsupportedConversionException;
use PhpUnitConversion\Unit;
use PhpUnitConversion\Unit\Mass\KiloGram;
use Spatie\ArrayToXml\ArrayToXml;

class ConvertRmShipmentDataToIpsXml
{
    public function handle(string $filename): bool
    {
        // Get CSV file.
        $reader = Reader::createFromString(Storage::disk('public')->get('/IPS/' . $filename));
        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();


        foreach ($records as $offset => $record) {
            $content = [
                'MailItem' => [
                    '_attributes' => ['ItemId' => $record['1D Tracking Number']],
                    'ItemWeight' => $this->getWeightInKg($record['ITEM WEIGHT'], 'kg'),
                    'Value' => $record['VALUE OF CONTENTS'],
                    'CurrencyCd' => 'GBP',
                    'DutiableInd' => 'D', // RM
                    'CustomNo' => 'ARGMT' . $record['arrangement_id'], // RM
                    'ClassCd' => 'U',
                    'Content' => $this->getContent($record), // RM
                    'OrigCountryCd' => 'GB',
                    'DestCountryCd' => $record['DELIVERY COUNTRY'],
                    'PostalStatusFcd' => 'MINL', // RM
                    'Addressee' => [
                        'Name' => e($record['RECIPIENT NAME']),
                        'Address' . e($record['DELIVERY ADDRESS 1']),
                        'City' => e($record['DELIVERY POST TOWN']),
                        'Postcode' => e($record['DELIVERY POSTCODE']),
                        'CountrySubEntity' => $record['DELIVERY COUNTRY'],
                        'PhoneNo' => $record['RECIPIENT TELEPHONE'],
                    ],
                    'Sender' => [
                        'Name' => e($record['SENDER NAME']),
                        'Address' => e($record['SENDER ADDRESS 1']),
                        'City' => e($record['SENDER POST TOWN']),
                        'Postcode' => e($record['SENDER POSTCODE']),
                        'CountrySubEntity' => 'UK',
                        'PhoneNo' => $record['SENDER TELEPHONE'],
                    ],
                    'ItemEvent' => [
                        'TNCd' => 'EventEMA', // RM
                        'Date' . Carbon::now()->toJSON(),
                        'OfficeCd' => $record['orig_office_code'], // route->orig_office_code
                    ],
                ]
            ];

            $xmlData = ArrayToXml::convert($content, [
                    'rootElementName' => 'ips',
                    '_attributes' => [
                            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                            'xmlns' => 'http://upu.int/ips',
                    ]
                ]
            );

            $date = new Carbon();
            $fileNameOutput = $date->format('YmdHis') . '_' . $record['tracking_number'] . '.xml';

            Storage::disk('public')->put('/IPS/output/' . $fileNameOutput, "\xEF\xBB\xBF" . $xmlData);
        }
        return true;
    }

    /**
     * @param float $weightValue
     * @param string $unit
     * @return float
     * @throws UnsupportedConversionException
     */
    private function getWeightInKg(float $weightValue, string $unit): float
    {
        // Float conversion added to stop errors with weights ending .0 or .00.
        $weightString = ((float) $weightValue) . ' ' . $unit;
        return Unit::from($weightString)
            ->to(KiloGram::class)
            ->getValue();
    }

    private function getContent(array $record): string
    {
        return $record['CATEGORY/NATURE OF ITEM'] == 'D' ? 'D' : 'M';
    }

    private function getClassCode(string $serviceName): string
    {
        $classCode = '';
        return $classCode;
    }
}