execute "add dotdeb repo" do
  user "root"
  not_if "grep 'deb http://packages.dotdeb.org wheezy all' /etc/apt/sources.list"
  command "echo 'deb http://packages.dotdeb.org wheezy all' >> /etc/apt/sources.list && echo 'deb-src http://packages.dotdeb.org wheezy all' >> /etc/apt/sources.list"
end

execute "add dotdeb key" do
  user "root"
  not_if "apt-key finger | grep 'Key fingerprint = 6572 BBEF 1B5F F28B 28B7  0683 7E3F 0700 89DF 5277'"
  command "wget http://www.dotdeb.org/dotdeb.gpg && apt-key add dotdeb.gpg && apt-get update"
end

# Run apt-get update to create the stamp file
execute "apt-get-update" do
  command "apt-get update"
  ignore_failure true
  not_if do ::File.exists?('/var/lib/apt/periodic/update-success-stamp') end
end

# For other recipes to call to force an update
execute "apt-get update" do
  command "apt-get update"
  ignore_failure true
  action :nothing
end

# provides /var/lib/apt/periodic/update-success-stamp on apt-get update
package "update-notifier-common" do
  notifies :run, resources(:execute => "apt-get-update"), :immediately
end

execute "apt-get-update-periodic" do
  command "apt-get update"
  ignore_failure true
  only_if do
    File.exists?('/var/lib/apt/periodic/update-success-stamp') &&
    File.mtime('/var/lib/apt/periodic/update-success-stamp') < Time.now - 86400
  end
end

# install the software we need
%w(
curl
vim
git
libapache2-mod-php5
php5-cli
php5-curl
php5-gd
php5-intl
php5-mysql
mysql-server
php5-mcrypt
php5-memcached
php-apc
redis-server
php5-redis
htop
unzip
).each { | pkg | package pkg }

# upgrade system packages
execute "apt-get-upgrade-system" do
  command "apt-get upgrade"
  ignore_failure false
  only_if do
    File.exists?('/var/lib/apt/periodic/update-success-stamp') &&
    File.mtime('/var/lib/apt/periodic/update-success-stamp') < Time.now - 86400
  end
end

execute "apache-enable-mod-rewrite" do
  user "root"
  command "a2enmod rewrite"
  notifies :reload, "service[apache2]"
end

execute "apache-enable-mod-ssl" do
  user "root"
  command "a2enmod ssl"
  notifies :reload, "service[apache2]"
end

service "apache2" do
  supports :restart => true, :reload => true, :status => true
  action [ :enable, :start ]
end

execute "check if date.timezone is Europe/Berlin in /etc/php5/apache2/php.ini?" do
  user "root"
  not_if "grep '^date.timezone = Europe/Berlin' /etc/php5/apache2/php.ini"
  command "sed -i 's/;date.timezone =.*/date.timezone = Europe\\/Berlin/g' /etc/php5/apache2/php.ini"
end

execute "check if date.timezone is Europe/Berlin in /etc/php5/cli/php.ini?" do
  user "root"
  not_if "grep '^date.timezone = Europe/Berlin' /etc/php5/cli/php.ini"
  command "sed -i 's/;date.timezone =.*/date.timezone = Europe\\/Berlin/g' /etc/php5/cli/php.ini"
end

execute "check if memory_limit is set to the correct value in /etc/php5/apache2/php.ini?" do
  user "root"
  not_if "grep 'memory_limit = 256M' /etc/php5/apache2/php.ini"
  command "sed -i 's/memory_limit =.*/memory_limit = 256M/g' /etc/php5/apache2/php.ini"
end

execute "check if memory_limit is set to the correct value /etc/php5/cli/php.ini?" do
  user "root"
  not_if "grep 'memory_limit = 512M' /etc/php5/cli/php.ini"
  command "sed -i 's/memory_limit =.*/memory_limit = 512M/g' /etc/php5/cli/php.ini"
end

execute "check if max_execution_time is set to the correct value in /etc/php5/apache2/php.ini?" do
  user "root"
  not_if "grep 'max_execution_time = 60' /etc/php5/apache2/php.ini"
  command "sed -i 's/max_execution_time =.*/max_execution_time = 60/g' /etc/php5/apache2/php.ini"
end
