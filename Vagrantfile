Vagrant.configure("2") do |config|

  # CentOS 7 base box
  # Toevoegen met:
  #   vagrant box add bento/centos-7.3 --provider virtualbox
  config.vm.box = "bento/centos-7.3"

  # Create a private network, which allows host-only access to the machine
  # using a specific IP.
  config.vm.network "private_network", ip: "192.168.33.10"

  if Vagrant::Util::Platform.windows?
    config.vm.provision "shell", inline: "sudo yum install epel-release -y"
    config.vm.provision "shell", inline: "sudo yum install ansible -y"
    config.vm.provision "guest_ansible" do |ansible|
      ansible.playbook = "ansible/playbook.yml"
    end
  else
    config.vm.provision "ansible" do |ansible|
      ansible.playbook = "ansible/playbook.yml"
    end
  end

  # Web root synchroniseren
  config.vm.synced_folder "./www/html", "/var/www/html", type: "virtualbox"
  # Fix permissies (apache user bestaat pas na provisioning, mounten gebeurt ervoor)
  config.vm.provision "shell", inline: "sudo mount -t vboxsf -o uid=`id -u apache`,gid=`id -g apache`,dmode=775,fmode=664 var_www_html /var/www/html"
end
