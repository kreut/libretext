<template>
  <div>
    <vue-bootstrap-typeahead
      v-model="query"
      :data="tags"
      placeholder="Enter a tag"
    />
    <div class="mt-3">
      <b-button variant="success" v-on:click="getQuestionsByTags()">Get Questions</b-button>
      <b-button variant="primary" v-on:click="getStudentView(assignmentId)">View as Student</b-button>
    </div>
    <div v-for="question in questions" :key="question.id" class="mt-5">
      <b-card v-bind:title="question.title" v-bind:sub-title="question.author">
        <div v-if="question.inAssignment" class="mt-1 mb-2" v-on:click="removeQuestion(question)">
          <b-button variant="danger">Remove</b-button>
        </div>
        <div v-else class="mt-1 mb-2" v-on:click="addQuestion(question)">
          <v-button>Add</v-button>
        </div>
        <b-card-text>
          <b-embed type="iframe"
                   aspect="16by9"
                   v-bind:src="`https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=${question.technology_id}`"
                   allowfullscreen
          ></b-embed>
        </b-card-text>
      </b-card>
    </div>
  </div>
</template>

<script>
  import axios from 'axios'
  import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'

  export default {
    components: {
      VueBootstrapTypeahead
    },
    middleware: 'auth',
    data: () => ({
      fields: [
        'title',
        'author',
        {
          key: 'technology_id',
          label: 'Question'
        },
        'actions'
      ],
      query: '',
      tags: [],
      questions: []
    }),
    mounted() {
      this.assignmentId = this.$route.params.assignmentId
      this.tags = this.getTags();

    },
    methods: {
      getTags() {
        try {
          axios.get(`/api/tags`).then(
            response => {
              console.log(response.data)
              this.tags = response.data
            })
        } catch (error) {
          alert(error.message)
        }

      },
      async addQuestion(question) {
        try {
          await axios.post(`/api/assignments/${this.assignmentId}/questions/${question.id}`)
          this.$noty.success('The question has been added to the assignment.')
          question.inAssignment = true

        } catch (error) {
          this.$noty.error('We could not add the question to the assignment.  Please try again or contact us for assistance.')
        }

      },
      async removeQuestion(question) {
        try {
          axios.delete(`/api/assignments/${this.assignmentId}/questions/${question.id}`)
          this.$noty.info('The question has been removed from the assignment.')
          question.inAssignment = false
        } catch (error) {
          this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
        }

      },
      async getQuestionsByTags() {
        try {
          if (this.query === '') {
            this.$noty.error('Please enter at least one tag.')
            return false
          }
          const {data} = await axios.post(`/api/questions/getQuestionsByTags`, {'tags': this.query})
          if (data.type === 'success') {
            let assignmentQuestions = await axios.get(`/api/assignments/${this.assignmentId}/questions`)
            for (let i = 0; i < data.questions.length; i++) {
              data.questions[i].inAssignment = assignmentQuestions.data.includes(data.questions[i].id);
            }
            this.questions = data.questions
          } else {
            this.$noty.error(`There are no questions associated with <strong>${this.query}</strong>.`)
          }

        } catch (error) {
          alert(error.message)
        }
      },
      getStudentView(assignmentId) {
       this.$router.push(`/assignments/${assignmentId}/questions/view`)
      }
    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
</script>
