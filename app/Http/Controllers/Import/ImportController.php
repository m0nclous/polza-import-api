<?php

namespace App\Http\Controllers\Import;

use App\Models\Wp\Term;
use App\Services\Import1CService;
use Illuminate\Routing\Controller;

class ImportController extends Controller
{
    public function getGroups(Import1CService $import1CService)
    {
        dd($import1CService->createGroups());

        dd(Term::find(1)->meta);
    }
}
