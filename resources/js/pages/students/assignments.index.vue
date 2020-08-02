<template>
  <div>
    <PageTitle title="Assignments"></PageTitle>
    <div v-if="hasAssignments">
      <b-table striped hover :fields="fields" :items="assignments">
        <template v-slot:cell(name)="data">
          <div class="mb-0">
            <a href="" v-on:click.prevent="getStudentView(data.item.id)">{{ data.item.name }}</a>
          </div>
        </template>
      </b-table>
    </div>
    <div v-else>
      <br>
      <div class="mt-4">
      <b-alert :show="showNoAssignmentsAlert" variant="warning"><a href="#" class="alert-link">This course currently has
        no assignments.</a></b-alert>
      </div>
    </div>
  </div>
</template>

<script>
  import axios from 'axios'



  const now = new Date()


  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
  let formatDateAndTime = value => {
    let date = new Date(value)
    return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear() +  ' ' + date.toLocaleTimeString()
  }


  export default {
    middleware: 'auth',
    data: () => ({
      assignments: [],
      courseId: false,
      fields: [
        'name',
        {
          key: 'available_from',
          formatter: value => {
            return formatDateAndTime(value)
          }
        },
        {
          key: 'due',
          formatter: value => {
            return formatDateAndTime(value)
          }
        },
        'credit_given_if_at_least'
      ],
      hasAssignments: false,
      showNoAssignmentsAlert: false,
    }),
    mounted() {
      this.courseId = this.$route.params.courseId
      this.getAssignments()
    },
    methods: {
      getStudentView(assignmentId) {
        this.$router.push(`/assignments/${assignmentId}/questions/view`)
      },
      async getAssignments() {
        try {
         const {data}  = await axios.get(`/api/courses/${this.courseId}/assignments`)
          console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
          this.hasAssignments = data.length > 0
          this.showNoAssignmentsAlert = !this.hasAssignments
          this.assignments = data

        } catch (error) {
          alert(error.response)
        }
      },
      metaInfo() {
        return {title: this.$t('home')}
      }
    }
  }
</script>
