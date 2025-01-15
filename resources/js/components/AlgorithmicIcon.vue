<template>
  <span v-if="(!user || user && user.role !== 3) && algorithmicQuestion"
        v-b-tooltip.hover="{ delay: { show: 500, hide: 0 } }"
        style="cursor: pointer;"
        :title="getTooltipText ()"
  >

    <font-awesome-icon
      v-if="!isTitle"
      :icon="randomIcon"
      :class="algorithmicAssignment ? 'text-success' : 'text-warning'"
    />
    <font-awesome-icon
      v-if="isTitle"
      :icon="randomIcon"
      style="font-size:.75em;margin-left:-5px"
      :class="algorithmicAssignment ? 'text-success' : 'text-warning'"
    />
  </span>
</template>

<script>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faRandom } from '@fortawesome/free-solid-svg-icons'
import { mapGetters } from 'vuex'

export default {
  name: 'AlgorithmicIcon',
  components: {
    FontAwesomeIcon
  },
  props: {
    isInstructorWithAnonymousView: {
      type: Boolean,
      default: false
    },
    isTitle: {
      type: Boolean,
      default: false
    },
    algorithmicQuestion: {
      type: Boolean,
      default: false
    },
    algorithmicAssignment: {
      type: Boolean,
      default: false
    }
  },
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({
    tooltipText: '',
    randomIcon: faRandom
  }),
  methods: {
    getTooltipText () {
      if (this.algorithmicAssignment) {
        return 'This question will be algorithmically generated.'
      } else {
        return this.isInstructorWithAnonymousView
          ? 'This is an algorithmic question and will be algorithmically if it is part of an algorithmic assignment.'
          : 'This is an algorithmic question but it will not be algorithmically generated unless your assignment properties are updated.'
      }
    }
  }
}
</script>

<style scoped>

</style>
