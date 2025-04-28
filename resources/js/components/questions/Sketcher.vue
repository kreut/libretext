<template>
  <div>
    <iframe
      id="sketcher"
      ref="sketcher"
      v-resize="{ log: false }"
      width="100%"
      src="/api/sketcher"
      frameborder="0"
      @load="loadStructure"
    />
    <ErrorMessage v-if="errorMessage" :message="errorMessage"/>
  </div>
</template>
<script>

import ErrorMessage from '../ErrorMessage.vue'

export default {
  name: 'Sketcher',
  components: { ErrorMessage },
  props: {
    errorMessage: {
      type: String,
      default: ''
    },
    solutionStructure: {
      type: Object,
      default: () => {
      }
    }
  },
  methods: {
    loadStructure () {
      console.log('loading solutionStructure')

      this.$refs.sketcher.contentWindow.postMessage({
        method: 'load',
        structure: this.solutionStructure
      }, '*')
    }
  }
}
</script>
