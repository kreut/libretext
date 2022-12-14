<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-save-framework"/>
    <b-modal id="modal-delete-framework"
             :title="`Delete ${frameworkToDelete.title}`"
             @hidden="frameworkToDelete = {}"
    >
      <p>
        You are about to delete the framework <strong>{{ frameworkToDelete.title }}</strong> and all associated
        descriptors. Any questions currently aligned with this framework will have their associated meta-tags removed.
      </p>
      <p>
        This action cannot be undone.
      </p>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide('modal-delete-framework')">
          Cancel
        </b-button>
        <b-button size="sm" variant="danger" @click="handleDeleteFramework()">
          Delete
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-framework-properties"
             title="Framework Properties"
             :size="isFrameworkOwner ? 'xl' : 'lg'"
             @hidden="frameworkToEdit = 0"
    >
      <FrameworkProperties :key="`framework-properties-${frameworkPropertiesKey}`"
                           :framework-form="frameworkForm"
                           :is-framework-owner="isFrameworkOwner"
      />
      <template #modal-footer="{ ok, cancel }">
        <span v-if="isFrameworkOwner">
          <b-button size="sm" @click="$bvModal.hide('modal-framework-properties')">
            Cancel
          </b-button>
          <b-button size="sm" variant="primary" @click="handleSaveFramework()">
            Save
          </b-button>
        </span>
        <span v-if="!isFrameworkOwner">
          <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-framework-properties')">
            OK
          </b-button>
        </span>
      </template>
    </b-modal>
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
        <PageTitle title="Frameworks"/>
        <b-container>
          <b-row align-h="end" class="mb-4">
            <b-button
              size="sm"
              variant="primary"
              @click="initNewFramework"
            >
              New Framework
            </b-button>
          </b-row>
        </b-container>
        <div class="clearfix mt-2">
          <b-table
            v-show="frameworks.length"
            aria-label="Frameworks"
            striped
            hover
            :no-border-collapse="true"
            :items="frameworks"
            :fields="frameworkFields"
          >
            <template #cell(title)="data">
              <router-link
                :to="{name: 'framework.view', params: {frameworkId: data.item.id}}"
              >
                {{ data.item.title }}
              </router-link>
            </template>
            <template #cell(actions)="data">
              <b-tooltip :target="getTooltipTarget('editFrameworkProperties',data.item.id)"
                         delay="500"
                         triggers="hover"
              >
                {{ data.item.user_id === user.id ? 'Edit' : 'View' }} {{ data.item.title }} framework properties
              </b-tooltip>
              <a :id="getTooltipTarget('editFrameworkProperties',data.item.id)"
                 href=""
                 class="pr-1"
                 @click.prevent="initEditFrameworkProperties(data.item.id)"
              >
                <b-icon
                  icon="gear"
                  class="text-muted"
                  :aria-label="`Edit framework properties for ${data.item.title}`"
                />
              </a>
              <b-tooltip :target="getTooltipTarget('deleteFramework',data.item.id)"
                         delay="500"
                         triggers="hover"
              >
                Delete {{ data.item.title }} framework
              </b-tooltip>
              <a v-if="data.item.user_id === user.id"
                 :id="getTooltipTarget('deleteFramework',data.item.id)"
                 href=""
                 class="pr-1"
                 @click.prevent="initDeleteFramework(data.item)"
              >
                <b-icon
                  icon="trash"
                  class="text-muted"
                  :aria-label="`Delete ${data.item.title} framework`"
                />
              </a>
              <b-tooltip :target="getTooltipTarget('exportFramework',data.item.id)"
                         delay="500"
                         triggers="hover"
              >
                Export {{ data.item.title }} framework
              </b-tooltip>
              <a :id="getTooltipTarget('exportFramework',data.item.id)"
                 href=""
                 class="pr-1"
                 @click.prevent="exportFramework(data.item)"
              >
                <b-icon
                  icon="download"
                  class="text-muted"
                  :aria-label="`Export ${data.item.title} framework`"
                />
              </a>
            </template>
          </b-table>
          <div v-show="!frameworks.length">
            <b-alert show variant="info">
              There are currently no frameworks.
            </b-alert>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import Form from 'vform/src'
import FrameworkProperties from '~/components/FrameworkProperties'
import AllFormErrors from '~/components/AllFormErrors'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'
import axios from 'axios'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import { downloadFile } from '~/helpers/DownloadFiles'
import { licenseOptions } from '~/helpers/Licenses'

const defaultFrameworkForm = {
  title: '',
  type: null,
  description: '',
  author: '',
  descriptor_type: null,
  license: null,
  licenseVersion: null
}
export default {
  components: {
    FrameworkProperties,
    AllFormErrors,
    Loading
  },
  metaInfo () {
    return { title: 'Frameworks' }
  },
  middleware: 'auth',
  props: {
    frameworkId: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    isFrameworkOwner: false,
    licenseOptions: licenseOptions,
    frameworkToDelete: {},
    frameworkToEdit: 0,
    frameworkPropertiesKey: 0,
    isLoading: true,
    framework: {},
    frameworks: [],
    allFormErrors: [],
    frameworkForm: new Form(defaultFrameworkForm),
    frameworkFields: [
      'title',
      {
        key: 'descriptor_type',
        label: 'type'
      },
      'description',
      'author',
      'actions'
    ]
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    })
  },
  mounted () {
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.getFrameworks()
  },
  methods: {
    exportFramework (framework) {
      let url = `/api/frameworks/export/${framework.id}`
      let license = this.licenseOptions.find(item => item.value === framework.license).text
      if (framework.license_version) {
        license += (framework.license_version)
      }
      let filename = `${framework.title}-${framework.author}-${license}-${framework.source_url}`
      filename.replace(/[/\\?%*:|"<>]/g, '-')
      downloadFile(url, [], `${filename}.csv`, this.$noty)
    },
    async handleDeleteFramework () {
      try {
        const { data } = await axios.delete(`/api/frameworks/${this.frameworkToDelete.id}/1`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        await this.getFrameworks()
        this.$bvModal.hide('modal-delete-framework')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initDeleteFramework (framework) {
      this.frameworkToDelete = framework

      this.$bvModal.show('modal-delete-framework')
    },
    async initEditFrameworkProperties (frameworkId) {
      try {
        const { data } = await axios.get(`/api/frameworks/${frameworkId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        this.frameworkToEdit = frameworkId
        this.frameworkForm = new Form(data.properties)
        this.isFrameworkOwner = data.properties.user_id === this.user.id
        this.frameworkPropertiesKey++
        this.$bvModal.show('modal-framework-properties')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getFrameworks () {
      try {
        const { data } = await axios.get('/api/frameworks')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        this.frameworks = data.frameworks
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    async handleSaveFramework () {
      try {
        const { data } = this.frameworkToEdit
          ? await this.frameworkForm.patch(`/api/frameworks/${this.frameworkToEdit}`)
          : await this.frameworkForm.post('/api/frameworks')
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          await this.getFrameworks()
          this.$bvModal.hide('modal-framework-properties')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.frameworkForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-save-framework')
        }
      }
    },
    initNewFramework () {
      defaultFrameworkForm.author = `${this.user.first_name} ${this.user.last_name}`
      this.frameworkForm = new Form(defaultFrameworkForm)
      this.isFrameworkOwner = true
      this.$bvModal.show('modal-framework-properties')
    }
  }
}
</script>
