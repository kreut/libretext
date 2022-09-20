<template>
  <div>
    <b-form-group>
      <b-form-checkbox-group
        v-model="selectedAllThatApply"
        :options="selectAllThatApplyOptions"
        name="select-all-that-apply"
        stacked
      />
    </b-form-group>
  </div>
</template>

<script>
export default {
  name: 'MultipleResponseSelectAllThatApplyOrSelectN',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    selectedAllThatApply: [],
    selectAllThatApplyOptions: []
  }),
  mounted () {
    for (let i = 0; i < this.qtiJson.responses.length; i++) {
      let response = this.qtiJson.responses[i]
      this.selectAllThatApplyOptions.push({ text: response.value, value: response.identifier })
      if (response.selected) {
        console.log(response.identifier)
        this.selectedAllThatApply.push(response.identifier)
      }
    }
  }
}
</script>

<style scoped>

</style>
