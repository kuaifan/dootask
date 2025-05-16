import emitter from "../../store/events";

let microAggregate = [];

const setMicroAggregate = (names) => {
    microAggregate = names;
}

const hasMicroAggregate = () => {
    return microAggregate.length > 0;
}

const closeLastMicroAggregate = () => {
    const name = microAggregate.pop();
    if (!name) {
        return false;
    }
    emitter.emit("observeMicroApp:close", name);
    return true;
}

export { setMicroAggregate, hasMicroAggregate, closeLastMicroAggregate};
