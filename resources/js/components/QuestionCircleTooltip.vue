<template>
  <a :id="id"
     href=""
     :class="'text-dark'"
     :aria-label="ariaLabel"
     @click.prevent
  >
    <b-icon :style="iconStyle" icon="question-circle"/>
  </a>
</template>

<script>
import _ from 'lodash'

export default {
  name: 'QuestionCircleTooltip',
  props: {
    iconStyle: {
      type: String,
      default: ''
    },
    color: {
      type: String,
      default: 'text-muted'
    },
    id: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    ariaLabel: ''
  }),
  mounted () {
    this.ariaLabel = _.upperFirst(this.id.replace('_', ' '))
    this.ariaLabel = _.replace(this.ariaLabel, /_|-/g, ' ')
  }
}
</script>

<style scoped>

</style>
