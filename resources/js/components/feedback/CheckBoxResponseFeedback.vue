<template>
  <span v-if="studentResponse && studentResponse.length">
    <b-icon-check-circle-fill v-if="response.correctResponse && studentResponse.includes(response.identifier)"
                              class="text-success"
    />
    <b-icon-x-circle-fill v-if="response.correctResponse && !studentResponse.includes(response.identifier)"
                          class="text-danger"
    />
    <b-icon-x-circle-fill v-if="!response.correctResponse && studentResponse.includes(response.identifier)"
                          class="text-danger"
    />
    <b-icon-check-circle-fill v-if="!response.correctResponse && !studentResponse.includes(response.identifier)"
                          class="text-success"
    />
  </span>
</template>

<script>
export default {
  props: {
    responses: {
      type: Array,
      default: () => {
      }
    },
    identifier: {
      type: String,
      default: ''
    },
    studentResponse: {
      type: Array,
      default: () => {
      }
    }
  },
  data: () => ({
    response: {}
  }),
  mounted () {
    this.response = this.responses.find(item => item.identifier === this.identifier)
  }
}
</script>

<style scoped>

</style>
