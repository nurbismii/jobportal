<?php

// Run: php -S 127.0.0.1:8765 tests/Browser/applicant-excel-export.php
$root = dirname(__DIR__, 2);
$view = file_get_contents($root . '/resources/views/admin/lamaran/index.blade.php');
if (! preg_match('/customize: (function\(xlsx\) \{[\s\S]*?\n {20}\})/', $view, $match)) {
    throw new RuntimeException('Applicant Excel customization was not found.');
}
?>
<!doctype html>
<html lang="en">
<meta charset="utf-8">
<title>Applicant Excel export regression check</title>
<h1>Applicant Excel export regression check</h1>
<pre id="result">Running...</pre>
<script><?php readfile($root . '/public/admin/vendor/jquery/jquery.min.js'); ?></script>
<script>
const customize = <?= $match[1] ?>;
const mainNs = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
const relNs = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
const packageNs = 'http://schemas.openxmlformats.org/package/2006/relationships';
const url = 'https://example.test/dokumen-pelamar/6401010101900001/CV & "draft".pdf?x=1&y=2';
const longUrl = 'https://example.test/dokumen-pelamar/6401010101900001/' + 'a'.repeat(280) + '.pdf';
const assert = (condition, message) => { if (!condition) throw new Error(message); };
const xmlText = value => $('<div>').text(value).html().replace(/"/g, '&quot;');
const cases = [
    ['original columns', { BP2: url, BQ2: longUrl }, false],
    ['hidden and optional columns', { D2: url, AA2: longUrl }, false],
    ['shared strings', { BP2: url }, true],
    ['empty export', {}, false],
];
try {
    for (const [label, expected, shared] of cases) {
        const cells = Object.entries(expected).map(([ref, value]) => shared
            ? `<c r="${ref}" t="s"><v>0</v></c>`
            : `<c r="${ref}" t="inlineStr"><is><t>${xmlText(value)}</t></is></c>`).join('');
        const sheet = $.parseXML(`<worksheet xmlns="${mainNs}" xmlns:r="${relNs}"><sheetData>
            <row r="1"><c r="A1" t="inlineStr"><is><t>No KTP</t></is></c><c r="B1" t="inlineStr"><is><t>No HP</t></is></c></row>
            <row r="2"><c r="A2" t="inlineStr"><is><t>0012345678901234</t></is></c><c r="B2" t="inlineStr"><is><t>08123456789</t></is></c>
            <c r="C2" t="inlineStr"><is><t>—</t></is></c>${cells}
            <c r="CR2" t="inlineStr"><is><t>javascript:alert(1)</t></is></c>
            <c r="CS2" t="inlineStr"><is><t>file:///private/cv.pdf</t></is></c></row>
            </sheetData><mergeCells count="0"/></worksheet>`);
        const xlsx = { xl: { worksheets: { 'sheet1.xml': sheet } } };
        if (shared) xlsx.xl['sharedStrings.xml'] = $.parseXML(`<sst xmlns="${mainNs}"><si><t>${xmlText(url)}</t></si></sst>`);
        customize(xlsx);
        const serializedSheet = $.parseXML(new XMLSerializer().serializeToString(sheet));
        const links = Array.from(serializedSheet.getElementsByTagNameNS(mainNs, 'hyperlink'));
        assert(links.length === Object.keys(expected).length, `${label}: hyperlink count`);
        assert($('c[r="A2"]', serializedSheet).attr('t') === 'inlineStr' && $('c[r="A2"] t', serializedSheet).text() === '0012345678901234', `${label}: NIK preserved`);
        assert($('c[r="B2"] t', serializedSheet).text() === '08123456789', `${label}: phone preserved`);
        assert($('f', serializedSheet).length === 0, `${label}: no formulas required`);
        if (links.length) {
            const rels = $.parseXML(new XMLSerializer().serializeToString(xlsx.xl.worksheets._rels['sheet1.xml.rels']));
            assert(rels.documentElement.namespaceURI === packageNs, `${label}: relationship namespace`);
            for (const link of links) {
                const id = link.getAttributeNS(relNs, 'id');
                const relationship = $('Relationship', rels).filter(function() { return this.getAttribute('Id') === id; });
                assert(relationship.length === 1, `${label}: relationship exists`);
                assert(relationship.attr('Target') === expected[link.getAttribute('ref')], `${label}: URL preserved`);
                assert(relationship.attr('TargetMode') === 'External' && relationship.attr('Type') === relNs + '/hyperlink', `${label}: external hyperlink`);
            }
        } else {
            assert(!xlsx.xl.worksheets._rels, `${label}: no empty relationship file`);
        }
    }
    document.getElementById('result').textContent = 'PASS: ' + cases.map(test => test[0]).join(', ') + '.\nNIK/phone, special characters, long URLs and safe URL schemes verified.';
} catch (error) {
    document.getElementById('result').textContent = 'FAIL: ' + error.message;
    throw error;
}
</script>
</html>
