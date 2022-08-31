<template>
  <div>
    {{ qtiJson }}
    <b-container>
      <b-row>
        <b-col>
          <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Actions to take</h2>">
            <b-card-text>
              <b-row v-for="(actionToTake, index) in qtiJson.actionsToTake" :key="actionToTake.identifier"
                     class="pb-3"
              >
                <b-col sm="2">
                  <label>
                    <b-icon-trash scale="1.1"
                                  @click="removeBowTieItem('action to take','actionsToTake',actionToTake.identifier)"
                    />
                  </label>
                </b-col>
                <b-col>
                  <b-textarea v-model="actionToTake.value"
                              rows="2"
                  />
                </b-col>
              </b-row>

              <b-button class="primary" size="sm" @click="addBowTieItem('actionsToTake')">
                Add Action To Take
              </b-button>
            </b-card-text>
          </b-card>
        </b-col>
        <b-col>
          <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Potential Conditions</h2>">
            <b-card-text>
              <b-row v-for="(potentialCondition, index) in qtiJson.potentialConditions"
                     :key="potentialCondition.identifier"
                     class="pb-3"
              >
                <b-col sm="1" class="pt-3 pr-2">
                  <label>
                    <b-icon-trash scale="1.2"
                                  @click="removeBowTieItem('potential condition','potentialConditions',potentialCondition.identifier)"
                    />
                  </label>
                </b-col>
                <b-col>
                  <b-textarea v-model="potentialCondition.value"
                              rows="2"
                  />
                </b-col>
              </b-row>
              <b-button class="primary" size="sm" @click="addBowTieItem('potentialConditions')">
                Add Potential Condition
              </b-button>
            </b-card-text>
          </b-card>
        </b-col>
        <b-col>
          <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Parameters To Monitor</h2>">
            <b-card-text>
              <b-row v-for="(parameterToMonitor, index) in qtiJson.parametersToMonitor"
                     :key="parameterToMonitor.identifier"
                     class="pb-3"
              >
                <b-col sm="1" class="pt-3 pr-2">
                  <label>
                    <b-icon-trash scale="1.2"
                                  @click="removeBowTieItem('parameter to monitor','parametersToMonitor',parameterToMonitor.identifier)"
                    />
                  </label>
                </b-col>
                <b-col>
                  <b-textarea v-model="parameterToMonitor.value"
                              rows="2"
                  />
                </b-col>
              </b-row>
              <b-button class="primary" size="sm" @click="addBowTieItem('parametersToMonitor')">
                Add Parameters to Monitor
              </b-button>
            </b-card-text>
          </b-card>
        </b-col>
      </b-row>
    </b-container>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'

export default {
  name: 'BowTie',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    }
  },
  methods: {
    removeBowTieItem (name, bowTieItems, identifier) {
      if (this.qtiJson[bowTieItems].length === 1) {
        this.$noty.info(`You need at least one ${name}.`)
        return false
      }
      this.qtiJson[bowTieItems] = this.qtiJson[bowTieItems].filter(bowTieItem => bowTieItem.identifier !== identifier)
    },
    addBowTieItem (bowTieItem) {
      this.qtiJson[bowTieItem].push({ identifier: uuidv4(), value: '' })
      this.$forceUpdate()
    }
  }
}
</script>

<style scoped>

</style>
