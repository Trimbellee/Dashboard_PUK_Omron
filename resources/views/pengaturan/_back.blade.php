<a href="{{ route('pengaturan.index') }}"
   class="back-btn">
    <span class="back-arrow">←</span>
    <span>Kembali</span>
</a>
<style>
    .back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px 6px 10px;
    margin-bottom: 8px;
    font-size: 13.5px;
    font-weight: 500;
    color: #2563EB;                          /* biru */
    background: rgba(37, 99, 235, 0.08);     /* biru tipis */
    border: 1px solid rgba(37, 99, 235, 0.15);
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.back-btn:hover {
    background: #2563EB;                     /* solid biru */
    color: #ffffff;
    border-color: #2563EB;
    transform: translateX(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}

.back-btn:hover .back-arrow {
    transform: translateX(-3px);
}

.back-btn:active {
    transform: translateX(-1px) scale(0.98);
}

.back-arrow {
    display: inline-block;
    font-size: 15px;
    line-height: 1;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .back-btn {
        color: #93C5FD;
        background: rgba(59, 130, 246, 0.12);
        border-color: rgba(59, 130, 246, 0.25);
    }
    .back-btn:hover {
        background: #3B82F6;
        color: #ffffff;
        border-color: #3B82F6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
    }
}
</style>