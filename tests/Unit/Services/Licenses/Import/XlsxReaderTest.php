<?php

namespace Tests\Unit\Services\Licenses\Import;

use PHPUnit\Framework\TestCase;
use RcnSistemas\Services\Licenses\Import\XlsxReader;
use ZipArchive;

final class XlsxReaderTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($this->path, ZipArchive::OVERWRITE);
        $main = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $zip->addFromString('xl/workbook.xml', '<workbook xmlns="' . $main . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Anual" sheetId="1" r:id="rId2"/><sheet name="Otra" sheetId="2" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Target="/xl/worksheets/sheet2.xml"/></Relationships>');
        $zip->addFromString('xl/sharedStrings.xml', '<sst xmlns="' . $main . '"><si><t>Serie</t></si><si><r><t>Nó</t></r><r><t>minas</t></r></si></sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="' . $main . '"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>otra hoja</t></is></c></row></sheetData></worksheet>');
        $zip->addFromString('xl/worksheets/sheet2.xml', '<worksheet xmlns="' . $main . '"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c></row><row r="3"><c r="A3"><v>46242</v></c><c r="C3" t="s"><v>0</v></c></row><row r="4"><c r="A4" t="inlineStr"><is><t>  </t></is></c></row></sheetData></worksheet>');
        $zip->close();
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    public function test_it_reads_sheets_by_name_with_shared_and_rich_strings(): void
    {
        $sheets = (new XlsxReader())->read($this->path);

        $this->assertSame(['A' => 'Serie', 'B' => 'Nóminas'], $sheets['Anual'][1]);
        $this->assertSame(['A' => '46242', 'C' => 'Serie'], $sheets['Anual'][3]);
        $this->assertArrayNotHasKey(4, $sheets['Anual']);
        $this->assertSame([1 => ['A' => 'otra hoja']], $sheets['Otra']);
    }
}
