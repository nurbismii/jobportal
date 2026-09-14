const { test } = require('node:test');
const assert = require('node:assert/strict');
const { processQueue } = require('../../public/admin/js/assessment-document-upload.js');

test('35 files are uploaded sequentially and retry only sends failed files', async () => {
    const items = Array.from({ length: 35 }, (_, id) => ({ id, state: 'pending' }));
    let active = 0;
    const sent = [];
    await processQueue(items, async item => {
        assert.equal(++active, 1);
        sent.push(item.id);
        await Promise.resolve();
        active--;
        if (item.id === 12) throw new Error('Connection lost after sending');
    }, () => {});
    assert.equal(sent.length, 35);
    assert.equal(items.filter(item => item.state === 'saved').length, 34);
    assert.equal(items[12].state, 'failed');
    const retried = [];
    await processQueue(items, async item => retried.push(item.id), () => {});
    assert.deepEqual(retried, [12]);
    assert.equal(items.filter(item => item.state === 'saved').length, 35);
});

test('invalid files are skipped, and session expiry stops subsequent requests', async () => {
    const items = [{ state: 'invalid' }, { state: 'pending' }, { state: 'pending' }];
    let calls = 0;
    await processQueue(items, async () => {
        calls++;
        const error = new Error('Session expired');
        error.stopQueue = true;
        throw error;
    }, () => {});
    assert.equal(calls, 1);
    assert.deepEqual(items.map(item => item.state), ['invalid', 'failed', 'failed']);
});
