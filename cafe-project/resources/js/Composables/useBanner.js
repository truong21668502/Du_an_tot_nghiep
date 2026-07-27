

export const THEMES = [
    { value: 'light', label: '☀️ Chữ tối (Light)' },
    { value: 'dark',  label: '🌙 Chữ sáng (Dark)' },
]

export const TEXT_ALIGNS = [
    { value: 'left',   label: '⬅️ Trái' },
    { value: 'center', label: '↔️ Giữa' },
    { value: 'right',  label: '➡️ Phải' },
]

// Thứ tự đọc trái → phải, trên → dưới — khớp trực tiếp với lưới CSS 3 cột
export const POSITIONS = [
    'top-left',    'top-center',    'top-right',
    'center-left', 'center',        'center-right',
    'bottom-left', 'bottom-center', 'bottom-right',
]

export const POSITION_LABELS = {
    'top-left':      'Trên • Trái',
    'top-center':    'Trên • Giữa',
    'top-right':     'Trên • Phải',
    'center-left':   'Giữa • Trái',
    'center':        'Giữa • Giữa',
    'center-right':  'Giữa • Phải',
    'bottom-left':   'Dưới • Trái',
    'bottom-center': 'Dưới • Giữa',
    'bottom-right':  'Dưới • Phải',
}