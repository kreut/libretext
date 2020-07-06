<template>
  <div>
    <div v-if="hasAssignments">
      <b-table striped hover :fields="fields" :items="assignments">
        <template v-slot:cell(name)="data">
          <a :href="`/assignments/${data.item.id}`">{{ data.item.name }}</a>
        </template>
      </b-table>
    </div>
    <div v-else>
      <b-alert :show="showNoAssignmentsAlert" variant="warning"><a href="#" class="alert-link">This course currently has no assignments.</a></b-alert>
    </div>
  </div>
</template>

<script>
  import axios from 'axios'

  export default {
    middleware: 'auth',
    data: () => ({
      fields: [
        {key: 'name', label: 'Assignment'},
        'available_on',
        'due_date'
      ],
      assignments: [],
      hasAssignments: false,
      showNoAssignmentsAlert: false
    }),
   mounted() {
      this.courseId = this.$route.params.courseId
      this.getAssignments();

    },
    methods: {
      getAssignments() {
        try {
          axios.get(`/api/courses/${this.courseId}/assignments`).then(
            response => {
              this.hasAssignments = response.data.length > 0
              this.showNoAssignmentsAlert = !this.hasAssignments;
             this.assignments = response.data
            }
          )
        } catch (error) {
          alert(error.response)
        }
      }
    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
</script>
