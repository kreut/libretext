<template>
  <div>
    <p>Choose one of the question types below to further narrow your search.</p>
    <div class="radio-grid">
      <div v-for="(option, index) in formattedQuestionTypesOptionsByTechnology"
           :key="index"
           class="radio-item"
           :style="`width:${width}`"
      >
        <label>
          <input v-model="formattedQuestionType"
                 type="radio"
                 :value="option.formatted_question_type"
                 @input="setFormattedQuestionType(option.formatted_question_type)"
          >
          {{ option.formatted_question_type }}
        </label>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FormattedQuestionTypeRadio',
  props: {
    technology: {
      type: String,
      default: ''
    },
    formattedQuestionTypesOptionsByTechnology: {
      type: Array,
      default: () => {
      }
    }
  },
  data: () => ({
    formattedQuestionType: '',
    width: ''
  }),
  mounted () {
    switch (this.technology) {
      case ('webwork'):
      case ('imathas'):
      case ('qti'):
        this.width = '100%'
        break
      case ('h5p'):
      case ('any'):
        this.width = '25%'
        break
    }
  },
  methods: {
    setFormattedQuestionType (formattedQuestionType) {
      this.$emit('setFormattedQuestionType', formattedQuestionType)
    }
  }
}
</script>

<style scoped>
.radio-grid {
  display: flex;
  flex-wrap: wrap;
}
</style>
