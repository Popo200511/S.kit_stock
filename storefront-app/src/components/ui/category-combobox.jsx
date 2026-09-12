import { useId, useState } from 'react'
import { ChevronDown, Check } from 'lucide-react'

export function CategoryCombobox({ options, value, onChange }) {
  const id = useId()
  const [open, setOpen] = useState(false)
  const [query, setQuery] = useState('')
  const [active, setActive] = useState(0)
  const label = (option) => option === 'ทั้งหมด' ? 'ทุกหมวดหมู่' : option
  const filtered = options.filter((option) => label(option).toLocaleLowerCase().includes(query.trim().toLocaleLowerCase()))
  function choose(option) {
    onChange(option)
    setOpen(false)
    setQuery('')
  }
  return <div className="catalog-category-field category-combobox" onBlur={(event) => {
    if (!event.currentTarget.contains(event.relatedTarget)) { setOpen(false); setQuery('') }
  }}>
    <label htmlFor={id}>หมวดหมู่สินค้า</label>
    <div className="category-combobox-input">
      <input id={id} role="combobox" aria-expanded={open} aria-controls={`${id}-list`} aria-autocomplete="list"
        aria-activedescendant={open && filtered[active] ? `${id}-option-${active}` : undefined}
        autoComplete="off" placeholder={label(value)} value={open ? query : label(value)}
        onFocus={() => { setOpen(true); setQuery(''); setActive(0) }}
        onClick={() => setOpen(true)}
        onChange={(event) => { setQuery(event.target.value); setOpen(true); setActive(0) }}
        onKeyDown={(event) => {
          if (event.key === 'Escape') { event.preventDefault(); setOpen(false); setQuery('') }
          if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault(); setOpen(true)
            const next = !open ? 0 : Math.max(0, Math.min(filtered.length - 1, active + (event.key === 'ArrowDown' ? 1 : -1)))
            setActive(next)
            document.getElementById(`${id}-option-${next}`)?.scrollIntoView({ block: 'nearest' })
          }
          if (event.key === 'Enter' && open) { event.preventDefault(); if (filtered[active]) choose(filtered[active]) }
        }} />
      <ChevronDown size={18} aria-hidden="true" />
    </div>
    {open && <ul id={`${id}-list`} role="listbox" aria-label="หมวดหมู่สินค้า" className="category-combobox-list">
      {filtered.map((option, index) => <li key={option} id={`${id}-option-${index}`} role="option"
        aria-selected={value === option} className={active === index ? 'is-active' : ''}
        onMouseDown={(event) => event.preventDefault()} onMouseMove={() => setActive(index)} onClick={() => choose(option)}>
        <span>{label(option)}</span>{value === option && <Check size={16} aria-hidden="true" />}
      </li>)}
      {!filtered.length && <li role="presentation" className="category-combobox-empty">ไม่พบหมวดหมู่</li>}
    </ul>}
  </div>
}
