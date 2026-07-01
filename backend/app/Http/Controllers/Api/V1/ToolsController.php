<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Tools\EmiCalculator;
use App\Services\Tools\LandUnitConverter;
use App\Services\Tools\NepaliDateConverter;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ToolsController extends Controller
{
    /** POST /api/v1/tools/emi */
    public function emi(Request $request)
    {
        $data = $request->validate([
            'principal'     => ['required', 'numeric', 'min:1'],
            'annual_rate'   => ['required', 'numeric', 'min:0', 'max:100'],
            'tenure_months' => ['required', 'integer', 'min:1', 'max:600'],
            'schedule'      => ['boolean'],
        ]);

        $result = EmiCalculator::calculate(
            (float) $data['principal'],
            (float) $data['annual_rate'],
            (int) $data['tenure_months'],
            (bool) ($data['schedule'] ?? true),
        );

        return ApiResponse::success($result, 'EMI calculated.');
    }

    /** POST /api/v1/tools/land-converter */
    public function landUnits(Request $request)
    {
        $data = $request->validate([
            'value' => ['required', 'numeric', 'min:0'],
            'from'  => ['required', 'string'],
            'to'    => ['nullable', 'string'],
        ]);

        if (! empty($data['to'])) {
            $converted = LandUnitConverter::convert((float) $data['value'], $data['from'], $data['to']);
            return ApiResponse::success([
                'value'     => (float) $data['value'],
                'from'      => strtolower($data['from']),
                'to'        => strtolower($data['to']),
                'result'    => $converted,
            ], 'Converted.');
        }

        // No target unit -> full ropani-system + metric breakdown.
        return ApiResponse::success(
            LandUnitConverter::breakdown((float) $data['value'], $data['from']),
            'Converted.'
        );
    }

    /** GET /api/v1/tools/land-units — list of supported units. */
    public function landUnitList()
    {
        return ApiResponse::success(LandUnitConverter::units(), 'Supported land units.');
    }

    /** POST /api/v1/tools/date-converter */
    public function dateConvert(Request $request)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:ad2bs,bs2ad'],
            'date'      => ['required_if:direction,ad2bs', 'nullable', 'date'],
            'year'      => ['required_if:direction,bs2ad', 'nullable', 'integer'],
            'month'     => ['required_if:direction,bs2ad', 'nullable', 'integer', 'between:1,12'],
            'day'       => ['required_if:direction,bs2ad', 'nullable', 'integer', 'between:1,32'],
        ]);

        $result = $data['direction'] === 'ad2bs'
            ? NepaliDateConverter::adToBs($data['date'])
            : NepaliDateConverter::bsToAd((int) $data['year'], (int) $data['month'], (int) $data['day']);

        return ApiResponse::success($result, 'Date converted.');
    }
}
