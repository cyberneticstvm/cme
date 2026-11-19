import test from 'node:test';
import assert from 'node:assert/strict';
import { hasDismissedPoster, rememberPosterDismissal, posterDismissalKey } from '../../public/js/poster-popup.js';

test('a first-time visitor has not dismissed the poster', () => {
    const storage = { getItem: () => null };

    assert.equal(hasDismissedPoster(storage), false);
});

test('dismissal survives a new page reading the same browser storage', () => {
    const values = new Map();
    const storage = {
        getItem: key => values.get(key) ?? null,
        setItem: (key, value) => values.set(key, value),
    };

    rememberPosterDismissal(storage, () => assert.fail('Cookie fallback is unnecessary'));

    const nextPageStorage = { getItem: key => values.get(key) ?? null };
    assert.equal(hasDismissedPoster(nextPageStorage), true);
    assert.equal(values.get(posterDismissalKey), '1');
});

test('an existing dismissal suppresses the poster', () => {
    const storage = { getItem: () => '1' };

    assert.equal(hasDismissedPoster(storage), true);
});

test('blocked local storage falls back to a persistent functional cookie', () => {
    const storage = {
        getItem: () => { throw new Error('Storage blocked'); },
        setItem: () => { throw new Error('Storage blocked'); },
    };
    let savedCookie;

    const persisted = rememberPosterDismissal(storage, value => { savedCookie = value; });

    assert.equal(persisted, true);
    assert.match(savedCookie, /Max-Age=315360000/);
    assert.equal(hasDismissedPoster(storage, savedCookie), true);
});

test('unrelated cookies and unset flags do not suppress the announcement', () => {
    const storage = { getItem: () => '0' };

    assert.equal(hasDismissedPoster(storage, 'another_cookie=1; visionx_event_poster_dismissed=0'), false);
});

test('dismissing the previous poster does not suppress the new announcement', () => {
    const storage = { getItem: key => key === 'visionx.poster.dismissed' ? '1' : null };

    assert.equal(hasDismissedPoster(storage, 'visionx_poster_dismissed=1'), false);
});

test('unavailable storage does not break dismissal or the website', () => {
    const storage = { setItem: () => { throw new Error('Storage blocked'); } };

    const persisted = rememberPosterDismissal(storage, () => { throw new Error('Cookies blocked'); });

    assert.equal(persisted, false);
});
