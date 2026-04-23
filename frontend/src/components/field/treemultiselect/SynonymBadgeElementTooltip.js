import React, { useCallback, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

/**
 * Hover tooltip for synonym badges. Native `title` is easy to miss; absolutely positioned
 * tooltips are clipped by the tree list (`overflow-y: auto` / row `overflow: hidden`), so this
 * renders the bubble in a fixed-position portal.
 */
export function SynonymBadgeElementTooltip({ text, width = 280, children }) {
    const wrapRef = useRef(null);
    const [open, setOpen] = useState(false);
    const [box, setBox] = useState(null);

    const tip = String(text || '').trim();

    const measure = useCallback(() => {
        const el = wrapRef.current;
        if (!el) return;
        const r = el.getBoundingClientRect();
        const margin = 8;
        const maxW = Math.min(320, (window.innerWidth || 800) - 24);
        const w = Math.min(width, maxW);
        let left = r.left - w - margin;
        if (left < 8) {
            left = r.right + margin;
        }
        if (left + w > (window.innerWidth || 800) - 8) {
            left = Math.max(8, (window.innerWidth || 800) - w - 8);
        }
        setBox({
            top: r.top + r.height / 2,
            left,
            width: w,
        });
    }, [width]);

    useEffect(() => {
        if (!open) {
            setBox(null);
            return;
        }
        measure();
        const onScrollOrResize = () => measure();
        window.addEventListener('scroll', onScrollOrResize, true);
        window.addEventListener('resize', onScrollOrResize);
        return () => {
            window.removeEventListener('scroll', onScrollOrResize, true);
            window.removeEventListener('resize', onScrollOrResize);
        };
    }, [open, measure]);

    if (!tip) {
        return children;
    }

    const bubble =
        open &&
        box && (
            <div
                style={{
                    position: 'fixed',
                    top: box.top,
                    left: box.left,
                    width: box.width,
                    maxWidth: 'min(100vw - 24px, 320px)',
                    transform: 'translateY(-50%)',
                    zIndex: 10050,
                    background: 'white',
                    boxShadow: '0 0 10px rgba(0, 0, 0, 0.1)',
                    padding: '10px',
                    fontSize: '12px',
                    borderRadius: '4px',
                    color: '#000',
                    pointerEvents: 'none',
                    boxSizing: 'border-box',
                }}
            >
                {tip}
            </div>
        );

    return (
        <>
            <span
                ref={wrapRef}
                style={{ display: 'inline-flex', alignItems: 'center' }}
                onMouseEnter={() => setOpen(true)}
                onMouseLeave={() => setOpen(false)}
            >
                {children}
            </span>
            {bubble && createPortal(bubble, document.body)}
        </>
    );
}
