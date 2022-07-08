<template>
  <div>
    <PageTitle title="Edit Empty Learning Tree Node"/>
    <CreateQuestion v-if="questionToEdit.id"
                    :question-to-edit="questionToEdit"
                    :modal-id="'edit-question'"/>
  </div>
</template>

<script>
import CreateQuestion from '~/components/questions/CreateQuestion'
import axios from 'axios'

export default {
  components: {
    CreateQuestion
  },
  data: () => ({
    questionToEdit: {}
  }),
  mounted () {
    this.getQuestionToEdit(this.$route.params.questionId)
  },
  methods: {
    async getQuestionToEdit (questionId) {
      try {
        const { data } = await axios.get(`/api/questions/get-question-to-edit/${questionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questionToEdit = data.question_to_edit
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

