import Isotope from 'isotope-layout'

export default {
  availableFilters: [{filter: '*', name: 'All'}],
  menuItems: [],
  filter: '*',
  buttons: null,
  container: null,

  init () {
    if ($('#isotope').length > 0)
      this.initIsotope()
  },

  finalize () {
  },

  initIsotope () {
    const loadIsotope = this.loadIsotope.bind(this)

    this.filter = window.location.hash.substring(1) ? window.location.hash.substring(1) : this.filter
    this.buttons = $('a[data-filter], button[data-filter]')

    this.addFilterMenu()
    $(window).load(loadIsotope)
  },

  addFilterMenu () {
    if (this.buttons.length > 0) {
      let all = `<li class="nav-item"><a href="#*" data-filter="*" class="nav-link">All</a></li>`
      this.menuItems.push(all)
    }

    for (let i = 0; i < this.buttons.length; i++) {
      let btn = $(this.buttons[i])
      let name = btn.text()
      let filter = btn.attr('data-filter')
      let index = this.availableFilters.findIndex(f => f.filter === filter)

      if (index === -1) {
        this.availableFilters.push({filter, name})

        let item = `<li class="nav-item"><a href="${filter}" data-filter="${filter}" class="nav-link">${name}</a></li>`

        this.menuItems.push(item)
      }
    }

    let filters = this.menuItems.join('')
    let menu = `<div class="filter-menu"><ul class="nav nav-pills">${filters}</div>`

    $('.page-header').append(menu)

    this.buttons = $('a[data-filter], button[data-filter]')
  },

  loadIsotope () {
    this.container = new Isotope('#isotope', {
      itemSelector: '.entry',
      layoutMode: 'fitRows',
      filter: () => true,
    })

    setTimeout(() => { this.container.arrange() }, 100)

    const handleFiltering = this.handleFiltering.bind(this)

    this.buttons.on('click', handleFiltering)
  },

  handleFiltering (event) {
    event.preventDefault()

    let btn = $(event.target)
    let filter = btn.attr('data-filter')

    if (filter !== this.filter) {
      this.container.arrange({ filter })
      this.filter = filter

      this.buttons.removeClass('active')

      for (var i = 0; i < this.buttons.length; i++) {
        if ($(this.buttons[i]).attr('data-filter') === this.filter) {
          $(this.buttons[i]).addClass('active')
        }
      }
    }
  },
}