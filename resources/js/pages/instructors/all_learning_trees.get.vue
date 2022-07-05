<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <PageTitle title="All Learning Trees"/>
        <b-container>
          <b-row class="pb-3">
            <b-col>
              <div v-if="learningTrees.length>0" class="overflow-auto">
                <b-pagination
                  v-model="currentPage"
                  :total-rows="totalRows"
                  :per-page="perPage"
                  align="center"
                  first-number
                  last-number
                  class="my-0"
                  @input="getAllLearningTrees()"
                />
              </div>
            </b-col>
            <b-col>
              <b-form-group
                label="Per page"
                label-for="all-learning-trees-per-page-select"
                label-cols-sm="3"
                label-align-sm="right"
                label-size="sm"
                class="mb-0"
              >
                <b-form-select
                  id="all-learning-trees-per-page-select"
                  v-model="perPage"
                  style="width:100px"
                  :options="perPageOptions"
                  size="sm"
                  @change="getAllLearningTrees"
                />
              </b-form-group>
            </b-col>
          </b-row>
        </b-container>
        <p>Full or partial-text matches will be counted when searching below.</p>
        <b-form-group
          label-for="all-questions-title"
          label-cols-sm="1"
          label-align-sm="right"
          label-size="sm"
          label="Title"
        >
          <b-input-group size="sm" style="width:400px">
            <b-form-input
              id="all-questions-author"
              v-model="title"
            />
          </b-input-group>
        </b-form-group>
        <b-form-group
          label-for="author"
          label-cols-sm="1"
          label-align-sm="right"
          label-size="sm"
          label="Author"
        >
          <b-input-group size="sm" style="width:400px">
            <b-form-input
              id="author"
              v-model="author"
            />
          </b-input-group>
        </b-form-group>
        <div style="margin-left:100px" class="mb-4">
          <b-button variant="primary" size="sm" @click="getAllLearningTrees">
            Update Results
          </b-button>
          <span class="font-weight-bold ml-5"> {{
              Number(totalRows).toLocaleString()
            }} learning trees</span>
        </div>
      </div>
    </div>
    <b-table
      id="all_learning_trees"
      aria-label="All Learning Trees"
      striped
      hover
      :no-border-collapse="true"
      :current-page="currentPage"
      :fields="fields"
      :items="learningTrees"
    >
      <template v-slot:cell(id)="data">
        <span :id="`learning_tree_id-${data.item.id}`">{{ data.item.id }}</span>
        <a href=""
           aria-label="Copy Learning Tree ID"
           @click.prevent="doCopy(`learning_tree_id-${data.item.id}`)"
        >
          <font-awesome-icon :icon="copyIcon" class="text-muted"/>
        </a>
      </template>
      <template v-slot:cell(title)="data">
        <a :href="`/instructors/learning-trees/editor/${data.item.id}/1`" target="_blank">{{ data.item.title }}</a>
      </template>
    </b-table>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import axios from 'axios'
import { mapGetters } from 'vuex'
import { doCopy } from '~/helpers/Copy'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'

export default {
  components: {
    Loading,
    FontAwesomeIcon
  },
  data: () => ({
      fields: [
        { key: 'id', label: 'ID' },
        'title', 'author'
      ],
      learningTreeId: 0,
      copyIcon: faCopy,
      perPageOptions: [10, 50, 100, 500, 1000],
      totalRows: 0,
      isLoading: true,
      title: '',
      author: '',
      currentPage: 1,
      perPage: 50,
      branchDescription: '',
      learningTrees: []
    }
  ),
  computed: {
    ...
      mapGetters({
        user: 'auth/user'
      })
  }
  ,
  created () {
    this.doCopy = doCopy
  }
  ,
  mounted () {
    if (this.user.role !== 2) {
      this.$noty.error('You do not have access to this page.')
      return false
    }
    this.isLoading = true
    this.getAllLearningTrees()
    this.isLoading = false
  }
  ,
  methods: {
    async getAllLearningTrees () {
      let allLearningTreesData = {
        current_page: this.currentPage,
        per_page: this.perPage,
        title: this.title,
        author: this.author,
        tags: this.branchDescription
      }
      try {
        const { data } = await axios.post('/api/learning-trees/all', allLearningTreesData)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.learningTrees = data.learning_trees
        this.totalRows = data.total_rows
        console.log(this.learningTrees)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }

  }

}
</script>

<style scoped>

</style>
