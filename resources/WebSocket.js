/**
 * WebSocket Client for Samsung Tizen TV Control
 */
class JeetizenWebSocket {
    constructor(url) {
        this.url = url;
        this.ws = null;
        this.messageId = 0;
        this.callbacks = {};
    }

    connect() {
        return new Promise((resolve, reject) => {
            try {
                this.ws = new WebSocket(this.url);
                
                this.ws.onopen = () => {
                    console.log('JeeTizen: Connecté au TV');
                    resolve();
                };
                
                this.ws.onmessage = (event) => {
                    this.handleMessage(event.data);
                };
                
                this.ws.onerror = (error) => {
                    console.error('JeeTizen: Erreur WebSocket', error);
                    reject(error);
                };
                
                this.ws.onclose = () => {
                    console.log('JeeTizen: Déconnecté du TV');
                };
            } catch (error) {
                reject(error);
            }
        });
    }

    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
    }

    sendKey(key) {
        const message = {
            method: 'ms.remote.control',
            params: {
                Cmd: 'Click',
                DataOfCmd: key,
                Option: 'false',
                TypeOfRemote: 'SendRemoteKey'
            }
        };
        return this.send(message);
    }

    send(message) {
        return new Promise((resolve, reject) => {
            if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
                reject(new Error('WebSocket non connecté'));
                return;
            }

            this.messageId++;
            message.id = this.messageId;
            
            this.callbacks[this.messageId] = {
                resolve,
                reject,
                timeout: setTimeout(() => {
                    delete this.callbacks[this.messageId];
                    reject(new Error('Timeout'));
                }, 5000)
            };

            try {
                this.ws.send(JSON.stringify(message));
            } catch (error) {
                reject(error);
            }
        });
    }

    handleMessage(data) {
        try {
            const message = JSON.parse(data);
            
            if (message.id && this.callbacks[message.id]) {
                const callback = this.callbacks[message.id];
                clearTimeout(callback.timeout);
                
                if (message.error) {
                    callback.reject(new Error(message.error));
                } else {
                    callback.resolve(message);
                }
                
                delete this.callbacks[message.id];
            }
        } catch (error) {
            console.error('JeeTizen: Erreur parsing message', error);
        }
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = JeetizenWebSocket;
}