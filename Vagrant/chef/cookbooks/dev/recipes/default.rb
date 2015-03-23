include_recipe "default"

# Install dev only software.
%w(
php5-xdebug
).each { | pkg | package pkg }

# Change apache user to avoid permission issues.
execute "Change apache user in /etc/apache2/apache2.conf" do
  user "root"
  not_if "grep '^User vagrant' /etc/apache2/apache2.conf"
  command "echo 'User vagrant' >> /etc/apache2/apache2.conf"
end

execute "Change apache group in /etc/apache2/apache2.conf" do
  user "root"
  not_if "grep '^Group vagrant' /etc/apache2/apache2.conf"
  command "echo 'Group vagrant' >> /etc/apache2/apache2.conf"
end

execute "Set default server name in /etc/apache2/apache2.conf" do
  user "root"
  not_if "grep '^ServerName' /etc/apache2/apache2.conf"
  command "echo 'ServerName sites.dev' >> /etc/apache2/apache2.conf"
end

vhosts = []
begin
  vhosts = data_bag("vhosts")
rescue
  puts "Vhost data bag is empty"
end
vhosts.each do | name |
  vhost = data_bag_item("vhosts", name)
  conffile = "/etc/apache2/sites-available/#{vhost['name']}.conf"
  if vhost['type'] == "SSL"
    execute "Generate certificate" do
        user "root"
        command "openssl req -x509 -newkey rsa:2048 -keyout /tmp/#{vhost['name']}.key.pem -out /tmp/#{vhost['name']}.crt.pem -days 365 -nodes -subj '/C=DE/ST=Bavaria/L=Munic/CN=#{vhost['name']}' && mv /tmp/#{vhost['name']}.crt.pem /etc/ssl/certs/#{vhost['name']}.crt.pem && mv /tmp/#{vhost['name']}.key.pem /etc/ssl/private/#{vhost['name']}.key.pem"
        not_if "test -f /etc/ssl/certs/#{vhost['name']}.crt.pem && test -f /etc/ssl/private/#{vhost['name']}.key.pem"
    end
    template conffile do
      user "root"
      mode "0644"
      source "ssl_vhost.conf.erb"
      cookbook "default"
      notifies :reload, "service[apache2]"
      variables ({
        :name => vhost['name'],
        :aliases => vhost['aliases'],
        :docroot => vhost['docroot']
      })
    end
  else
    template conffile do
      user "root"
      mode "0644"
      source "vhost.conf.erb"
      cookbook "default"
      notifies :reload, "service[apache2]"
      variables ({
        :name => vhost['name'],
        :aliases => vhost['aliases'],
        :docroot => vhost['docroot']
      })
    end
  end
  execute "Remove not needed vhosts" do
    user "root"
    command "cd /etc/apache2/sites-available && rm `ls | grep -v '^#{vhost['name']}.conf$'` && cd /etc/apache2/sites-enabled && rm `ls | grep -v '^#{vhost['name']}.conf$'`"
    only_if "ls /etc/apache2/sites-available | grep -v '^#{vhost['name']}.conf$'"
  end
  execute "Link vhost to enabled sites" do
    user "root"
    command "ln -s #{conffile} /etc/apache2/sites-enabled/#{vhost['name']}.conf"
    not_if "test -L /etc/apache2/sites-enabled/#{vhost['name']}.conf"
  end
end

# Set up Xdebug.
xdebug = data_bag_item("config", "xdebug")

template "/etc/php5/mods-available/xdebug.ini" do
  user "root"
  mode "0644"
  source "xdebug.ini.erb"
  notifies :reload, "service[apache2]"
  variables ({
    :hostip => xdebug['hostip']
  })
end
execute "Activate Xdebug" do
  user "root"
  command "ln -s /etc/php5/mods-available/xdebug.ini /etc/php5/conf.d/20-xdebug.ini"
  not_if "test -L /etc/php5/conf.d/20-xdebug.ini"
end
