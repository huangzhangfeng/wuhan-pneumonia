<template>
  <div class="page-content" v-if="pageReady">
    <!-- Statistics -->
    <h2>全球數據統計</h2>
    <statisticCard :virusSummary="virusSummary" :overseasSummary="overseasSummary"/>
    <!-- /Statistics -->

    <!-- Map Section -->
    <h2>中國及港澳台地區疫情地圖</h2>
    <map-card :provinceSummary="provinceSummary"/>
    <!-- /Map Section -->

    <!-- Overseas Section -->
    <h2>海外感染數據</h2>
    <overseas-table :overseasSummary="overseasSummary"/>
    <!-- /Overseas Section -->

    <app-footer sourceLink="https://ncov.dxy.cn/ncovh5/view/pneumonia" sourceName="丁香醫生" autoFetch/>
  </div>

  <error-message message="無法取得最新資訊。" v-else-if="pageError" />

  <loading v-else/>
</template>

<script>
import {
  loading,
  appFooter,
  errorMessage
} from '@components';
import { pneumoniaDataService } from '@services';
import mapCard from './mapCard.vue';
import statisticCard from './statisticCard.vue';
import overseasTable from './overseasTable.vue';

export default {
  components: {
    loading,
    statisticCard,
    mapCard,
    overseasTable,
    appFooter,
    errorMessage
  },
  data() {
    return {
      initFetch: true,
      pageReady: false,
      pageError: false,
      virusSummary: undefined,
      provinceSummary: undefined,
      overseasSummary: undefined,
      getLiveDataInterval: undefined
    };
  },
  methods: {
    getLiveData() {
      pneumoniaDataService.getLiveData().then(({ data }) => {
        const { getStatisticsService, getAreaStat, getListByCountryTypeService2 } = data;
        this.virusSummary = getStatisticsService;
        this.provinceSummary = getAreaStat;
        this.overseasSummary = getListByCountryTypeService2;
        this.pageReady = true;
      }).catch(() => {
        if (this.initFetch) {
          this.pageError = true;
        }
      }).finally(() => {
        this.initFetch = false;
      });
    }
  },
  mounted() {
    this.getLiveData();

    // Fetch new data every 5 minutes
    const autoFetchInterval = parseInt(process.env.VUE_APP_AUTO_FETCH_TIME, 10);
    this.getLiveDataInterval = setInterval(() => {
      this.getLiveData();
    }, autoFetchInterval);
  },
  beforeDestroy() {
    clearInterval(this.getLiveDataInterval);
  }
};
</script>
