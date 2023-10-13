<?php
    // Next site to go live details
    $next_site_name = "Prisons and Probation Ombudsman (PPO)";
    $next_site_url = "https://www.ppo.gov.uk/";
?>
<div class="govuk-width-container">
    <div class="govuk-grid-row govuk-!-margin-bottom-9">
        <div class="govuk-grid-column-one-third">
            <h2 class="govuk-heading-m">Sites hosted</h2>
            <span class="govuk-heading-xl"><?php echo get_site_count(); ?></span>
        </div>
        <div class="govuk-grid-column-one-third">
            <h2 class="govuk-heading-m">Site monitoring and performance</h2>
            <a href="https://github.com/ministryofjustice/hale-platform/actions/workflows/cd.yaml">
                <img src="https://github.com/ministryofjustice/hale-platform/actions/workflows/cd.yaml/badge.svg?branch=main" alt="Hale Platform Deployment">
            </a>
            <ul class="govuk-list">
                <li>
                    <a href="https://github.com/ministryofjustice/hale-platform">Hale Platform GitHub repository</a>
                </li>
                <li>
                    <a href="https://grafana.live.cloud-platform.service.justice.gov.uk/d/85a562078cdf77779eaa1add43ccec1e/kubernetes-compute-resources-namespace-pods?orgId=1&refresh=10s&var-datasource=default&var-cluster=&var-namespace=hale-platform-prod">Grafana dashboard (prod)</a>
                </li>
                <li> 
                    <a href="https://logs.cloud-platform.service.justice.gov.uk/_dashboards/app/discover#/?_g=(filters:!(),refreshInterval:(pause:!t,value:0),time:(from:now-15m,to:now))&_a=(columns:!(_source),filters:!(),index:b95d8900-dd15-11ed-87c8-170407f57c9c,interval:auto,query:(language:kuery,query:''),sort:!())">Ingress logs</a>
                </li> 
            </ul>    
        </div>
        <div class="govuk-grid-column-one-third">
            <h2 class="govuk-heading-m">Next site to go live</h2>
            <p><?php echo $next_site_name;?></p>
            <p><a href="<?php echo $next_site_url;?>"><?php echo $next_site_url;?></a></p>
        </div>
    </div>
</div>
