const {test} = require('node:test');
const assert = require('node:assert/strict');
const {scoreComponents, translatedLabel} = require('../../public/admin/js/field-test.js');

test('bilingual labels replace every placeholder and preserve literal user text', () => {
    const labels = {':answered/:count dijawab': ':answered/:count dijawab / 已答 :answered/:count 项', 'Nama :name': 'Nama :name / 姓名 :name'};
    assert.equal(translatedLabel(labels, ':answered/:count dijawab', {answered: 5, count: 6}), '5/6 dijawab / 已答 5/6 项');
    assert.equal(translatedLabel(labels, 'Nama :name', {name: '$& <王伟>'}), 'Nama $& <王伟> / 姓名 $& <王伟>');
    assert.equal(translatedLabel(labels, 'Catatan asli'), 'Catatan asli');
    assert.equal(scoreComponents(['Ya', 'Tidak'], 20).score, 10);
});

test('six equal components receive 20 points without cumulative rounding', () => {
    assert.equal(scoreComponents(Array(6).fill('Ya'), 20).score, 20);
    assert.equal(scoreComponents(['Ya', 'Ya', 'Ya', 'Ya', 'Ya', 'Tidak'], 20).score, 16.67);
    assert.equal(scoreComponents(['Ya', ...Array(5).fill('Tidak')], 20).score, 3.33);
});

test('unanswered and all No are different states', () => {
    assert.deepEqual(scoreComponents(Array(6).fill(null), 20), {score: null, answered: 0, yes: 0, count: 6});
    assert.deepEqual(scoreComponents(Array(6).fill('Tidak'), 20), {score: 0, answered: 6, yes: 0, count: 6});
    assert.deepEqual(scoreComponents(['Ya', null, null, null, null, null], 20), {score: 3.33, answered: 1, yes: 1, count: 6});
});

test('other aspect weights and counts are calculated proportionally', () => {
    assert.equal(scoreComponents(['Ya', ...Array(10).fill('Tidak')], 60).score, 5.45);
    assert.equal(scoreComponents(['Ya', 'Tidak'], 10).score, 5);
    assert.equal(scoreComponents(Array(7).fill('Ya'), 20).score, 20);
});
