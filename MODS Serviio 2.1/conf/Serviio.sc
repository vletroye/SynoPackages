[serviio_console]
title="Console"
desc="Serviio"
port_forward="yes"
dst.ports="23423/tcp"

[serviio_mediabrowser]
title="MediaBrowser"
desc="Serviio"
port_forward="yes"
dst.ports="23424/tcp"

[serviio_dlna]
title="DLNA"
desc="Serviio"
port_forward="no"
src.ports="1900/udp"
dst.ports="1900,8895/tcp,udp"
