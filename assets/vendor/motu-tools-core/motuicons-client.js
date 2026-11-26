/* MotuCloud Favicon — api.motucloud.com (Motuicons) On-Demand Vector Picker */
(function (global) {
    'use strict';

    const API_BASE = 'https://api.motucloud.com/api';
    const SEARCH_API_URL = 'https://api.motucloud.com/api/v1/search';

    const CURATED_PACKS = [
        {
            id: 'all',
            label: '全部商用库 (643 集)',
            license: 'SPDX 商用',
            isRemoteSearch: true,
            featured: []
        },
        {
            id: 'lucide',
            label: 'Lucide (1,901)',
            license: 'ISC',
            featured: [
                'rocket', 'sparkles', 'cloud', 'code-2', 'globe', 'shield', 'zap', 'flame',
                'compass', 'gem', 'book-open', 'layers', 'terminal', 'palette', 'feather', 'crown',
                'cpu', 'heart', 'star', 'anchor', 'atom', 'box', 'leaf', 'coffee'
            ]
        },
        {
            id: 'ri',
            label: 'Remix Icon (3,229)',
            license: 'Apache-2.0',
            featured: [
                'rocket-2-fill', 'sparkling-2-fill', 'cloud-fill', 'code-s-slash-line', 'global-line', 'shield-check-fill', 'flashlight-fill', 'fire-fill',
                'compass-3-fill', 'vip-diamond-fill', 'book-open-fill', 'stack-fill', 'terminal-box-fill', 'palette-fill', 'quill-pen-fill', 'vip-crown-fill',
                'cpu-line', 'heart-3-fill', 'star-fill', 'anchor-line', 'planet-fill', 'cube-line', 'leaf-fill', 'cup-fill'
            ]
        },
        {
            id: 'heroicons',
            label: 'Heroicons (1,288)',
            license: 'MIT',
            featured: [
                'rocket-launch-solid', 'sparkles-solid', 'cloud-solid', 'code-bracket-solid', 'globe-alt-solid', 'shield-check-solid', 'bolt-solid', 'fire-solid',
                'cube-solid', 'book-open-solid', 'command-line-solid', 'paint-brush-solid', 'cpu-chip-solid', 'heart-solid', 'star-solid', 'beaker-solid',
                'academic-cap-solid', 'briefcase-solid', 'camera-solid', 'chat-bubble-left-right-solid', 'envelope-solid', 'home-solid', 'key-solid', 'wrench-screwdriver-solid'
            ]
        },
        {
            id: 'tabler',
            label: 'Tabler (6,232)',
            license: 'MIT',
            featured: [
                'rocket', 'sparkles', 'cloud', 'code', 'world', 'shield-check', 'bolt', 'flame',
                'compass', 'diamond', 'book', 'stack-2', 'terminal-2', 'palette', 'feather', 'crown',
                'cpu', 'heart', 'star', 'anchor', 'atom', 'box', 'leaf', 'coffee'
            ]
        },
        {
            id: 'feather',
            label: 'Feather (286)',
            license: 'MIT',
            featured: [
                'zap', 'cloud', 'code', 'globe', 'shield', 'compass', 'book-open', 'layers',
                'terminal', 'feather', 'cpu', 'heart', 'star', 'anchor', 'box', 'coffee',
                'home', 'mail', 'lock', 'search', 'settings', 'send', 'sun', 'moon'
            ]
        }
    ];

    const ZH_SYNONYMS = {
        '首页': ['home', 'house'],
        '主页': ['home', 'house'],
        '房子': ['home', 'house'],
        '云': ['cloud'],
        '火箭': ['rocket', 'launch'],
        '代码': ['code', 'terminal', 'bracket'],
        '开发': ['code', 'terminal', 'cpu'],
        '终端': ['terminal', 'command'],
        '书': ['book', 'read'],
        '文档': ['file', 'doc', 'book'],
        '商店': ['shop', 'store', 'cart', 'bag'],
        '购物': ['cart', 'bag', 'shop'],
        '聊天': ['chat', 'message', 'comment'],
        '消息': ['message', 'chat', 'bell'],
        '邮件': ['mail', 'envelope', 'send'],
        '安全': ['shield', 'lock', 'key'],
        '盾': ['shield'],
        '锁': ['lock', 'key'],
        '闪电': ['zap', 'bolt', 'flash'],
        '太阳': ['sun'],
        '月亮': ['moon'],
        '心': ['heart'],
        '喜欢': ['heart', 'thumb'],
        '星': ['star', 'sparkle'],
        '收藏': ['star', 'bookmark'],
        '相机': ['camera', 'photo'],
        '图片': ['image', 'photo', 'camera'],
        '音乐': ['music', 'audio', 'headphone'],
        '视频': ['video', 'play', 'film'],
        '地球': ['globe', 'world', 'earth'],
        '网络': ['globe', 'wifi', 'world'],
        '设置': ['settings', 'cog', 'gear', 'sliders'],
        '齿轮': ['cog', 'gear', 'settings'],
        '画板': ['palette', 'brush', 'pen'],
        '设计': ['palette', 'pen', 'feather', 'layers'],
        '笔': ['pen', 'quill', 'edit', 'feather'],
        '搜索': ['search', 'magnify'],
        '工具': ['wrench', 'tool', 'hammer'],
        '链接': ['link', 'chain'],
        '图表': ['chart', 'bar', 'graph', 'trend'],
        '数据': ['database', 'chart', 'server'],
        '智能': ['sparkle', 'bot', 'brain', 'cpu'],
        '芯片': ['cpu', 'chip'],
        '咖啡': ['coffee', 'cup'],
        '叶子': ['leaf', 'sprout', 'tree'],
        '火': ['flame', 'fire'],
        '皇冠': ['crown', 'vip'],
        '钻石': ['gem', 'diamond'],
        '铃铛': ['bell', 'alarm'],
        '指南针': ['compass'],
        '地图': ['map', 'pin', 'location'],
        '礼物': ['gift'],
        '书签': ['bookmark'],
        '手机': ['phone', 'mobile'],
        '电脑': ['laptop', 'monitor', 'desktop'],
        '文件': ['file', 'folder'],
        '标签': ['tag', 'label'],
        '奖杯': ['trophy', 'award'],
        '眼睛': ['eye'],
        '钥匙': ['key'],
        '时间': ['clock', 'watch', 'timer'],
        '日历': ['calendar'],
        '用户': ['user', 'person'],
        '团队': ['users', 'group']
    };

    const packCache = {};
    const packPromises = {};

    function getPackMeta(packId) {
        for (let i = 0; i < CURATED_PACKS.length; i++) {
            if (CURATED_PACKS[i].id === packId) return CURATED_PACKS[i];
        }
        return CURATED_PACKS[0];
    }

    function loadPack(packId) {
        const meta = getPackMeta(packId);
        if (packCache[meta.id]) {
            return Promise.resolve(packCache[meta.id]);
        }
        if (packPromises[meta.id]) {
            return packPromises[meta.id];
        }

        const url = API_BASE + '/' + encodeURIComponent(meta.id) + '.json';
        const promise = fetch(url, { mode: 'cors', credentials: 'omit' })
            .then(function (resp) {
                if (!resp.ok) {
                    throw new Error('HTTP ' + resp.status);
                }
                return resp.json();
            })
            .then(function (json) {
                const iconsMap = (json && json.icons) || {};
                const names = Object.keys(iconsMap);
                const defaultW = Number(json && json.width) || Number(json && json.info && json.info.height) || 24;
                const defaultH = Number(json && json.height) || defaultW;
                const entry = {
                    id: meta.id,
                    meta: meta,
                    width: defaultW,
                    height: defaultH,
                    icons: iconsMap,
                    names: names
                };
                packCache[meta.id] = entry;
                delete packPromises[meta.id];
                return entry;
            })
            .catch(function (err) {
                delete packPromises[meta.id];
                throw err;
            });

        packPromises[meta.id] = promise;
        return promise;
    }

    function getIconRecord(pack, iconName) {
        if (!pack || !pack.icons || !iconName) return null;
        const item = pack.icons[iconName];
        if (!item || !item.body) return null;
        return {
            pack: pack.id,
            name: iconName,
            width: Number(item.width) || pack.width || 24,
            height: Number(item.height) || pack.height || 24,
            body: item.body
        };
    }

    function expandSearchTerms(rawQuery) {
        const clean = String(rawQuery || '').trim().toLowerCase();
        if (!clean) return [];
        const parts = clean.split(/[\s,，/]+/).filter(Boolean);
        const terms = [];
        parts.forEach(function (part) {
            terms.push(part);
            Object.keys(ZH_SYNONYMS).forEach(function (zhKey) {
                if (part.indexOf(zhKey) !== -1 || zhKey.indexOf(part) !== -1) {
                    ZH_SYNONYMS[zhKey].forEach(function (en) {
                        if (terms.indexOf(en) === -1) terms.push(en);
                    });
                }
            });
        });
        return terms;
    }

    function searchPack(pack, rawQuery) {
        if (!pack || !pack.names) return [];
        const terms = expandSearchTerms(rawQuery);
        if (terms.length === 0) {
            const featured = (pack.meta && pack.meta.featured) || [];
            const validFeatured = featured.filter(function (n) {
                return Boolean(pack.icons[n]);
            });
            if (validFeatured.length >= 12) {
                const rest = pack.names.filter(function (n) {
                    return validFeatured.indexOf(n) === -1;
                });
                return validFeatured.concat(rest);
            }
            return pack.names;
        }

        const exact = [];
        const starts = [];
        const contains = [];
        for (let i = 0; i < pack.names.length; i++) {
            const name = pack.names[i];
            const lower = name.toLowerCase();
            let matchedRank = 0;
            for (let t = 0; t < terms.length; t++) {
                const term = terms[t];
                if (lower === term) {
                    matchedRank = 3;
                    break;
                }
                if (lower.indexOf(term) === 0 && matchedRank < 2) {
                    matchedRank = 2;
                } else if (lower.indexOf(term) !== -1 && matchedRank < 1) {
                    matchedRank = 1;
                }
            }
            if (matchedRank === 3) exact.push(name);
            else if (matchedRank === 2) starts.push(name);
            else if (matchedRank === 1) contains.push(name);
        }
        return exact.concat(starts, contains);
    }

    function colorizeIconBody(body, colorHex) {
        const color = colorHex || '#FFFFFF';
        return String(body || '').replace(/currentColor/gi, color);
    }

    function buildColoredIconSvg(iconRecord, colorHex) {
        if (!iconRecord) return '';
        const w = iconRecord.width || 24;
        const h = iconRecord.height || 24;
        const color = colorHex || '#FFFFFF';
        const coloredBody = colorizeIconBody(iconRecord.body, color);
        return (
            '<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 ' +
            w + ' ' + h + '" color="' + color + '">' + coloredBody + '</svg>'
        );
    }

    function buildMiniPreviewSvg(iconRecord) {
        if (!iconRecord) return '';
        const w = iconRecord.width || 24;
        const h = iconRecord.height || 24;
        return (
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 ' +
            w + ' ' + h + '" aria-hidden="true">' + iconRecord.body + '</svg>'
        );
    }

    function searchRemoteAll(rawQuery, limit) {
        const q = String(rawQuery || '').trim();
        if (!q) {
            return Promise.resolve({ count: 0, icons: [] });
        }
        const url =
            SEARCH_API_URL +
            '?q=' + encodeURIComponent(q) +
            '&palette=false&limit=' + encodeURIComponent(String(limit || 24)) +
            '&include_svg=1';
        return fetch(url, { mode: 'cors', credentials: 'omit' })
            .then(function (resp) {
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                return resp.json();
            })
            .then(function (data) {
                const items = (data && data.icons) || [];
                const records = items
                    .filter(function (it) { return it && it.body; })
                    .map(function (it) {
                        return {
                            pack: it.set,
                            name: it.name,
                            width: Number(it.width) || 24,
                            height: Number(it.height) || 24,
                            body: it.body,
                            spdx: (it.license && it.license.spdx) || 'MIT'
                        };
                    });
                return {
                    count: records.length,
                    icons: records
                };
            });
    }

    global.MotuIconsPicker = {
        API_BASE: API_BASE,
        SEARCH_API_URL: SEARCH_API_URL,
        CURATED_PACKS: CURATED_PACKS,
        getPackMeta: getPackMeta,
        loadPack: loadPack,
        getIconRecord: getIconRecord,
        searchPack: searchPack,
        searchRemoteAll: searchRemoteAll,
        colorizeIconBody: colorizeIconBody,
        buildColoredIconSvg: buildColoredIconSvg,
        buildMiniPreviewSvg: buildMiniPreviewSvg
    };
})(window);
