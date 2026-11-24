#!/bin/sh

# Configurações
OUT="/root/speedtest-data.csv"
HEADER="Timestamp,Download(Mbps),Upload(Mbps),Ping(ms)"
MAX_RECORDS=1000
TEMP_FILE="/tmp/speedtest-temp.csv"

# Cria arquivo se não existir
[ ! -f "$OUT" ] && echo "$HEADER" > "$OUT"

# Executa o teste
DATA=$(/usr/local/bin/speedtest --accept-license --accept-gdpr --format=json)

if [ $? -eq 0 ]; then
    # Extrai timestamp UTC e converte para GMT-3 (FreeBSD compatible)
    UTC_TIMESTAMP=$(echo $DATA | jq -r '.timestamp')
    DATE_PART=$(echo $UTC_TIMESTAMP | cut -d'T' -f1)
    TIME_PART=$(echo $UTC_TIMESTAMP | cut -d'T' -f2 | sed 's/Z//')
    
    # Converte UTC para GMT-3 (FreeBSD não tem date -d, fazemos manualmente)
    HOUR=$(echo $TIME_PART | cut -d':' -f1)
    MINUTE=$(echo $TIME_PART | cut -d':' -f2)
    SECOND=$(echo $TIME_PART | cut -d':' -f3)
    
    # Subtrai 3 horas (GMT-3)
    HOUR=$((HOUR - 3))
    if [ $HOUR -lt 0 ]; then
        HOUR=$((HOUR + 24))
        # Opcional: ajustar a data para o dia anterior aqui se necessário
    fi
    
    # Formata com zero à esquerda
    HOUR=$(printf "%02d" $HOUR)
    TIMESTAMP="${DATE_PART} ${HOUR}:${MINUTE}:${SECOND}"

    # Extrai métricas
    DOWNLOAD=$(echo $DATA | jq '.download.bandwidth' | awk '{printf "%.2f", $1 * 8 / 1000000}')
    UPLOAD=$(echo $DATA | jq '.upload.bandwidth' | awk '{printf "%.2f", $1 * 8 / 1000000}')
    PING=$(echo $DATA | jq '.ping.latency' | awk '{printf "%.2f", $1}')

    # Adiciona novo registro
    echo "$TIMESTAMP,$DOWNLOAD,$UPLOAD,$PING" >> "$OUT"
    
    # Limita registros (FreeBSD tail não suporta -n diretamente)
    { echo "$HEADER"; tail -n $MAX_RECORDS "$OUT" | sed '1d'; } > "$TEMP_FILE"
    mv "$TEMP_FILE" "$OUT"
fi

cp /root/speedtest-data.csv /usr/local/www/speedtest.csv
chmod 644 /usr/local/www/speedtest.csv
