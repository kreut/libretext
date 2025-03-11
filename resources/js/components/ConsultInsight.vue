<template>
  <b-button
            class="ml-auto mr-1"
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
    }
  },
  methods: {
    consultInsight () {
      window.open(this.url, '_blank')
    },
  }
}
</script>
