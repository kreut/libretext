<template>
  <b-button
    :class="formattingClass"
    size="sm"
    variant="info"
    @click="consultInsight"
  >Consult Insight
  </b-button>

</template>

<script>
export default {
  name: 'ConsultInsight',
  props: {
    url: {
      type: String,
      default: ''
    },
    formattingClass: {
      type: String,
      default: 'ml-auto mr-1'
    }
  },
  methods: {
    consultInsight () {
      window.open(this.url, '_blank')
    },
  }
}
</script>
