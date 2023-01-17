<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use PhpUnitConversion\Exception\UnsupportedConversionException;
use PhpUnitConversion\Unit;
use PhpUnitConversion\Unit\Mass\KiloGram;
use Spatie\ArrayToXml\ArrayToXml;

class ConvertShipmentDataToIpsXml
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
                        '_attributes' => ['ItemId' => $record['tracking_number']],
                        'ItemWeight' => $this->getWeightInKg($record['weight_value'], $record['weight_unit']),
                        'Value' => $record['total_price_value'],
                        'CurrencyCd' => $record['total_price_currency_code'],
                        'DutiableInd' => 'D', // RM
                        'CustomNo' => 'ARGMT' . $record['arrangement_id'], // RM
                        'ClassCd' => $record['carrier_service_class_code'], // RM The class code in the carrier's (jersey posts) services. Search for getClassCode in the services folder in jp carrier.
                        'Content' => $this->getContent($record), // RM
                        'OrigCountryCd' => 'JE',
                        'DestCountryCd' => $record['to_country_code'],
                        'PostalStatusFcd' => 'MINL', //RM
                        'Addressee' => [
                            'Name' => e($record['to_given_name']),
                            'Address' . e($record['to_address_line_1']),
                            'City' => e($record['to_locality']),
                            'Postcode' => e($record['to_postal_code']),
                            'CountrySubEntity' => $record['to_country_code'],
                            'PhoneNo' => $record['to_phone'],
                        ],
                        'Sender' => [
                                'Name' => e($record['from_given_name']),
                                'Address' => e($record['from_address_line_1']),
                                'City' => e($record['from_locality']),
                                'Postcode' => e($record['from_postal_code']),
                                'CountrySubEntity' => $record['from_country_code'],
                                'PhoneNo' => $record['from_phone'],
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
        return $record['export_reason'] == 'documents' ? 'D' : 'M';
    }
}