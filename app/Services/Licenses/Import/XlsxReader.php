<?php

namespace RcnSistemas\Services\Licenses\Import;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Lee los valores de las hojas de un archivo .xlsx sin dependencias externas.
 * Devuelve celdas como texto; las fechas llegan como el número de serie de Excel.
 */
final class XlsxReader
{
    private const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /** @return array<string, array<int, array<string, string>>> filas por nombre de hoja, indexadas por número de fila y letra de columna */
    public function read(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo Excel: ' . $path);
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheets = [];
            foreach ($this->sheetPaths($zip) as $name => $sheetPath) {
                $xml = $zip->getFromName($sheetPath);
                if ($xml === false) {
                    throw new RuntimeException('El archivo Excel no contiene la hoja ' . $name . '.');
                }
                $sheets[$name] = $this->rows($this->load($xml), $sharedStrings);
            }

            return $sheets;
        } finally {
            $zip->close();
        }
    }

    /** @return array<string, string> */
    private function sheetPaths(ZipArchive $zip): array
    {
        $workbook = $this->load($this->entry($zip, 'xl/workbook.xml'));
        $relations = $this->load($this->entry($zip, 'xl/_rels/workbook.xml.rels'));
        $targets = [];
        foreach ($relations->children('http://schemas.openxmlformats.org/package/2006/relationships')->Relationship as $relation) {
            $target = ltrim($this->attribute($relation, 'Target'), '/');
            $targets[$this->attribute($relation, 'Id')] = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
        }

        $paths = [];
        foreach ($workbook->children(self::MAIN_NS)->sheets->sheet as $sheet) {
            $relationId = (string) $sheet->attributes(self::REL_NS)['id'];
            if (isset($targets[$relationId])) {
                $paths[$this->attribute($sheet, 'name')] = $targets[$relationId];
            }
        }

        return $paths;
    }

    /** @return list<string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $strings = [];
        foreach ($this->load($xml)->children(self::MAIN_NS)->si as $item) {
            $strings[] = $this->text($item);
        }

        return $strings;
    }

    /** @return array<int, array<string, string>> */
    private function rows(SimpleXMLElement $sheet, array $sharedStrings): array
    {
        $rows = [];
        foreach ($sheet->children(self::MAIN_NS)->sheetData->row as $row) {
            $cells = [];
            foreach ($row->children(self::MAIN_NS)->c as $cell) {
                if (!preg_match('/^([A-Z]+)(\d+)$/', $this->attribute($cell, 'r'), $reference)) {
                    continue;
                }
                $children = $cell->children(self::MAIN_NS);
                $value = match ($this->attribute($cell, 't')) {
                    's' => $sharedStrings[(int) $children->v] ?? '',
                    'inlineStr' => $this->text($children->is),
                    default => (string) $children->v,
                };
                if (trim($value) !== '') {
                    $cells[$reference[1]] = $value;
                }
            }
            if ($cells) {
                $rows[(int) $this->attribute($row, 'r')] = $cells;
            }
        }

        return $rows;
    }

    private function text(SimpleXMLElement $item): string
    {
        $children = $item->children(self::MAIN_NS);
        $text = isset($children->t) ? (string) $children->t : '';
        foreach ($children->r as $run) {
            $text .= (string) $run->children(self::MAIN_NS)->t;
        }

        return $text;
    }

    // Con namespaces, $element['x'] busca el atributo en el namespace del elemento; los de OOXML no llevan prefijo.
    private function attribute(SimpleXMLElement $element, string $name): string
    {
        return (string) ($element->attributes()[$name] ?? '');
    }

    private function entry(ZipArchive $zip, string $name): string
    {
        $xml = $zip->getFromName($name);
        if ($xml === false) {
            throw new RuntimeException('El archivo no es un Excel válido: falta ' . $name . '.');
        }

        return $xml;
    }

    private function load(string $xml): SimpleXMLElement
    {
        $element = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        if ($element === false) {
            throw new RuntimeException('El archivo Excel tiene contenido XML inválido.');
        }

        return $element;
    }
}
