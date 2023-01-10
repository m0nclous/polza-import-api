<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

abstract class AbstractFile1CService
{
    protected string $fileName;
    protected SimpleXMLElement $xml;

    public function getXmlStringContent(): string
    {
        return Storage::disk('local')->get('xml/' . $this->fileName);
    }

    public function getXml(): SimpleXMLElement
    {
        return $this->xml = $this->xml ?? simplexml_load_string($this->getXmlStringContent());
    }
}