import TableComponentAlpinePlugin from './components/table'

document.addEventListener('alpine:init', () => {
    window.Alpine.data('tableComponent', TableComponentAlpinePlugin)
})
