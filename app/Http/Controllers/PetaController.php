<?php

namespace App\Http\Controllers;

use App\Support\PetaData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PetaController extends Controller
{
    public function data(Request $request): JsonResponse
    {
        $data = PetaData::build([
            'jenis_rambu_id' => $request->query('jenis_rambu_id') ?: null,
            'tingkat' => $request->query('tingkat') ?: null,
            'kelurahan' => $request->query('kelurahan') ?: null,
            'kecamatan' => $request->query('kecamatan') ?: null,
        ]);

        return response()->json($data['pins']);
    }
}
