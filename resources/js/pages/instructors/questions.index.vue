<template>
  <div>
    <PageTitle title="Add Questions"></PageTitle>
    <vue-bootstrap-typeahead
      v-model="query"
      :data="tags"
      placeholder="Enter a tag"
      ref="queryTypeahead"
    />
    <div class="mt-3 d-flex flex-row">
      <b-button variant="primary" v-on:click="addTag()" class="mr-2">Add Tag</b-button>
      <b-button variant="success" v-on:click="getQuestionsByTags()" class="mr-2">Get Questions</b-button>
      <b-button variant="secondary" v-on:click="getStudentView(assignmentId)">View as Student</b-button>
    </div>
    <hr>
    <div>
      <h5>Chosen Tags:</h5>
      <div v-if="chosenTags.length>0">
      <ol>
        <li v-for="chosenTag in chosenTags" :key="chosenTag">
          <span v-on:click="removeTag(chosenTag)">{{ chosenTag}}<b-icon icon="trash" variant="danger"></b-icon></span>
        </li>
      </ol>
    </div>
    <div v-else>
      <span class="text-danger">No tags have been chosen.</span>
    </div>
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
                 v-bind:src="question.src"
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
  import {getSrc} from '~/helpers/Questions'

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
      questions: [],
      chosenTags: []
    }),
    created() {
      this.getSrc = getSrc
    },
    mounted() {
      this.assignmentId = this.$route.params.assignmentId
      this.tags = this.getTags();
    },
    methods: {
      removeTag(chosenTag) {
        this.chosenTags = _.without(this.chosenTags, chosenTag);
        console.log(this.chosenTags)
      },
      addTag() {

        if ((this.query !== '') && this.tags.includes(this.query) && !this.chosenTags.includes(this.query)) {
          this.chosenTags.push(this.query)
        }
        this.$refs.queryTypeahead.inputValue = this.query = '' //https://github.com/alexurquhart/vue-bootstrap-typeahead/issues/22
      },
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
        this.questions = []

       this.addTag() //in case they didn't click
        try {
          if (this.chosenTags.length === 0) {
            this.$noty.error('Please choose at least one tag.')
            return false
          }
          const {data} = await axios.post(`/api/questions/getQuestionsByTags`, {'tags': this.chosenTags})
          console.log(data)
          if (data.type === 'success') {
            //get whether in the assignment and get the url
            let assignmentQuestions = await axios.get(`/api/assignments/${this.assignmentId}/questions`)
            for (let i = 0; i < data.questions.length; i++) {
              data.questions[i].inAssignment = assignmentQuestions.data.includes(data.questions[i].id)
              data.questions[i].src = this.getSrc(data.questions[i])
            }
            this.questions = data.questions
          } else {

            this.$noty.error(`There are no questions associated with <strong>${this.chosenTags.join(" and ")}</strong>.`)
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
