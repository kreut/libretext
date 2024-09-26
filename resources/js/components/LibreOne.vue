<template>
  <span>
    <b-button id="libreverse-tooltip"
              :size="size"
              variant="outline-primary"
              class="launch-pad"
              @click="openLibreOne()"
    >
      <font-awesome-icon :icon="rocketIcon" title="Explore the Libreverse through its launchpad"/>
    </b-button>
    <b-tooltip target="libreverse-tooltip"
               delay="750"
               triggers="hover"
    >
      Explore the Libreverse through its launchpad
    </b-tooltip>
  </span>
</template>

<script>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faRocket } from '@fortawesome/free-solid-svg-icons'

export default {
  name: 'LibreOne',
  components: {
    FontAwesomeIcon
  },
  props: {
    size: {
      type: String,
      default: 'lg'
    }
  },
  data: () => ({
    rocketIcon: faRocket
  }),
  methods: {
    openLibreOne () {
      window.open(
        'https://one.libretexts.org/home', '_blank')
    }
  }
}
</script>
